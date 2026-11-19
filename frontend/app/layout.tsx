import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "AFYAMTOTO AI — Grandir en bonne santé",
  description:
    "AFYAMTOTO AI est une plateforme en préparation pour accompagner le suivi préventif des enfants de 0 à 5 ans en RDC.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fr">
      <body>{children}</body>
    </html>
  );
}
