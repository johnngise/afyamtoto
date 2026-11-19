(() => {
    const normalize = (value) => value.toLocaleLowerCase("fr").normalize("NFD").replace(/[\u0300-\u036f]/g, "");

    const refreshTable = (table) => {
        const search = document.querySelector(`[data-table-search="${table.id}"]`);
        const filter = document.querySelector(`[data-table-filter="${table.id}"]`);
        const pagination = document.querySelector(`[data-pagination="${table.id}"]`);
        const body = table.tBodies.item(0);
        if (!(body instanceof HTMLTableSectionElement)) {
            return;
        }

        const rows = [...body.rows].filter((row) => !row.hasAttribute("data-empty-state"));
        const query = normalize(search instanceof HTMLInputElement ? search.value.trim() : "");
        const selectedFilter = normalize(filter instanceof HTMLSelectElement ? filter.value.trim() : "");
        const matches = rows.filter((row) => {
            const textMatches = normalize(row.textContent ?? "").includes(query);
            const filterValue = normalize(row.dataset.status ?? "");
            return textMatches && (!selectedFilter || filterValue === selectedFilter);
        });

        const pageSize = Math.max(1, Number.parseInt(table.dataset.pageSize ?? "5", 10) || 5);
        const pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
        const page = Math.min(Math.max(1, Number.parseInt(table.dataset.currentPage ?? "1", 10) || 1), pageCount);
        table.dataset.currentPage = String(page);
        rows.forEach((row) => { row.hidden = true; });
        matches.slice((page - 1) * pageSize, page * pageSize).forEach((row) => { row.hidden = false; });

        const emptyState = body.querySelector("[data-empty-state]");
        if (emptyState instanceof HTMLTableRowElement) {
            emptyState.hidden = matches.length > 0;
        }

        const count = document.querySelector(`[data-table-count="${table.id}"]`);
        if (count instanceof HTMLElement) {
            count.textContent = `${matches.length} résultat${matches.length === 1 ? "" : "s"} fictif${matches.length === 1 ? "" : "s"}`;
        }

        if (pagination instanceof HTMLElement) {
            const previous = pagination.querySelector("[data-page-prev]");
            const next = pagination.querySelector("[data-page-next]");
            const status = pagination.querySelector("[data-page-status]");
            if (previous instanceof HTMLButtonElement) previous.disabled = page <= 1;
            if (next instanceof HTMLButtonElement) next.disabled = page >= pageCount;
            if (status instanceof HTMLElement) status.textContent = `${page} / ${pageCount}`;
        }
    };

    document.querySelectorAll("[data-dashboard-table]").forEach((table) => {
        if (table instanceof HTMLTableElement) refreshTable(table);
    });

    document.addEventListener("input", (event) => {
        if (!(event.target instanceof HTMLInputElement) || !event.target.matches("[data-table-search]")) {
            return;
        }

        const table = document.getElementById(event.target.dataset.tableSearch ?? "");
        if (table instanceof HTMLTableElement) {
            table.dataset.currentPage = "1";
            refreshTable(table);
        }
    });

    document.addEventListener("change", (event) => {
        if (!(event.target instanceof HTMLSelectElement) || !event.target.matches("[data-table-filter]")) {
            return;
        }

        const table = document.getElementById(event.target.dataset.tableFilter ?? "");
        if (table instanceof HTMLTableElement) {
            table.dataset.currentPage = "1";
            refreshTable(table);
        }
    });

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const pageButton = event.target.closest("[data-page-prev], [data-page-next]");
        if (pageButton instanceof HTMLButtonElement) {
            const pagination = pageButton.closest("[data-pagination]");
            const table = pagination instanceof HTMLElement ? document.getElementById(pagination.dataset.pagination ?? "") : null;
            if (table instanceof HTMLTableElement) {
                const current = Number.parseInt(table.dataset.currentPage ?? "1", 10) || 1;
                table.dataset.currentPage = String(current + (pageButton.hasAttribute("data-page-next") ? 1 : -1));
                refreshTable(table);
            }
            return;
        }

        const addButton = event.target.closest('[data-demo-action="add-row"]');
        if (addButton instanceof HTMLButtonElement) {
            const form = addButton.closest("[data-demo-form]");
            const table = form instanceof HTMLElement ? document.getElementById(form.dataset.targetTable ?? "") : null;
            const body = table instanceof HTMLTableElement ? table.tBodies.item(0) : null;
            if (!(form instanceof HTMLElement) || !(table instanceof HTMLTableElement) || !(body instanceof HTMLTableSectionElement)) {
                return;
            }

            const fields = [...form.querySelectorAll("[data-row-value]")];
            const invalidField = fields.find((field) => {
                if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement)) return false;
                return field.required && !field.checkValidity();
            });
            if (invalidField instanceof HTMLInputElement || invalidField instanceof HTMLSelectElement) {
                invalidField.reportValidity();
                return;
            }

            const values = new Map(fields.map((field) => [
                (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) ? field.dataset.rowValue ?? "" : "",
                (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) ? field.value.trim() : "",
            ]));
            const columns = (table.dataset.tableColumns ?? "").split(",").filter(Boolean);
            const demoId = `DEMO-NEW-${body.querySelectorAll("tr:not([data-empty-state])").length + 1}`;
            const row = document.createElement("tr");
            row.dataset.demoAdded = "true";

            for (const column of columns) {
                const cell = document.createElement("td");
                const value = column === "id" ? demoId : values.get(column) ?? "";
                cell.textContent = value;
                if (column === "status") {
                    row.dataset.status = normalize(value);
                    const badge = document.createElement("span");
                    badge.className = "dashboard-status";
                    badge.textContent = value;
                    cell.replaceChildren(badge);
                }
                row.append(cell);
            }

            const emptyState = body.querySelector("[data-empty-state]");
            body.insertBefore(row, emptyState);
            table.dataset.currentPage = "1";
            refreshTable(table);
            form.querySelectorAll('input:not([type="hidden"])').forEach((input) => { input.value = ""; });
            form.querySelectorAll("select").forEach((select) => { select.selectedIndex = 0; });

            const modal = form.closest(".modal");
            if (modal instanceof HTMLElement && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).hide();
            }

            window.dispatchEvent(new CustomEvent("afyamtoto:toast", {
                detail: { message: "Ajout effectué dans l’aperçu local uniquement ; rien n’a été enregistré." },
            }));
            return;
        }

        const chartButton = event.target.closest("[data-chart-range]");
        if (chartButton instanceof HTMLButtonElement) {
            const card = chartButton.closest(".dashboard-chart-card");
            const line = card?.querySelector("[data-chart-line]");
            if (!(line instanceof SVGPolylineElement)) {
                return;
            }

            const range = chartButton.dataset.chartRange;
            const points = range === "month" ? line.dataset.pointsMonth : line.dataset.pointsWeek;
            if (points) line.setAttribute("points", points);
            card.querySelectorAll("[data-chart-range]").forEach((button) => {
                button.setAttribute("aria-pressed", String(button === chartButton));
            });
            const label = card.querySelector("[data-chart-period-label]");
            if (label instanceof HTMLElement) {
                label.textContent = range === "month" ? "12 périodes fictives" : "8 périodes fictives";
            }
        }
    });
})();
