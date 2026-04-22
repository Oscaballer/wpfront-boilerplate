/**
 * components/Container/Container.tsx
 *
 * Wrapper de ancho máximo con padding horizontal consistente.
 * Úsalo en todos los layouts para mantener la alineación.
 *
 * @example
 * <Container>
 *   <h1>Contenido</h1>
 * </Container>
 *
 * <Container size="sm" as="section">
 *   <p>Texto estrecho</p>
 * </Container>
 */
import type { ElementType, ReactNode } from "react";
import styles from "./Container.module.css";

type ContainerSize = "sm" | "md" | "lg" | "xl" | "2xl";

interface ContainerProps {
  children: ReactNode;
  /** Ancho máximo del contenedor. Default: "xl" (1280px) */
  size?: ContainerSize;
  /** Elemento HTML que renderiza. Default: "div" */
  as?: ElementType;
  className?: string;
}

export default function Container({
  children,
  size = "xl",
  as: Tag = "div",
  className,
}: ContainerProps) {
  const classes = [
    styles.container,
    styles[`size-${size}`],
    className,
  ]
    .filter(Boolean)
    .join(" ");

  return <Tag className={classes}>{children}</Tag>;
}
