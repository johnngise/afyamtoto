import type { Metadata, Viewport } from "next";
import { Manrope, DM_Sans } from "next/font/google";
import "./globals.css";
import { SiteHeader } from "@/app/components/site-header";
import { SiteFooter } from "@/app/components/site-footer";

const manrope = Manrope({
  subsets: ["latin"],
  weight: ["400", "500", "600", "700", "800"],
  variable: "--font-manrope",
  display: "swap",
});

const dmSans = DM_Sans({
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
  variable: "--font-dm-sans",
  display: "swap",
});

export const metadata: Metadata = {
  title: "AFYAMTOTO AI — Grandir en bonne santé",
  description:
    "AFYAMTOTO AI est une plateforme en préparation pour accompagner le suivi préventif des enfants de 0 à 5 ans en RDC.",
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 5,
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fr" className={`${manrope.variable} ${dmSans.variable}`}>
      <body className={`${dmSans.variable}`}>
        <SiteHeader />
        <main className="page-container">{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}
