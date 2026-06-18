import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Providers from "./providers";
import Header from "@/components/Header/Header";
import Footer from "@/components/Footer/Footer";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

const siteName = process.env.NEXT_PUBLIC_SITE_NAME || "Next.js WP Boilerplate";

export const metadata: Metadata = {
  title: {
    template: `%s | ${siteName}`,
    default: siteName,
  },
  description:
    process.env.NEXT_PUBLIC_SITE_DESCRIPTION ||
    "Starter template: Next.js 16 + Headless WordPress + CSS Modules + Docker + GCP.",
  icons: {
    icon: [
      { url: "/assets/favicon-32x32.png", sizes: "32x32", type: "image/png" },
      { url: "/assets/favicon-16x16.png", sizes: "16x16", type: "image/png" },
    ],
    shortcut: "/favicon.ico",
    apple: "/favicon.ico",
  },
};

import { getLocale } from "@/lib/utils/i18n";

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const locale = await getLocale();

  return (
    <html
      lang={locale}
      data-scroll-behavior="smooth"
      className={`${geistSans.variable} ${geistMono.variable}`}
    >
      <body>
        <Providers>
          <Header />
          <main>{children}</main>
          <Footer />
        </Providers>
      </body>
    </html>
  );
}
