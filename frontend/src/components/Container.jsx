/** Limita el ancho del contenido y aplica el margen lateral del tema. */
export function Container({ children, className = '' }) {
  return <div className={`container ${className}`.trim()}>{children}</div>;
}

/** Bloque de página con espaciado vertical. `alt` pone fondo blanco; `title` añade un h2. */
export function Section({ title, alt = false, children }) {
  return (
    <section className={`section${alt ? ' section--alt' : ''}`}>
      <Container>
        {title && <h2 className="section__title">{title}</h2>}
        {children}
      </Container>
    </section>
  );
}
