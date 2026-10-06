import { Container, Section } from '../../components/Container.jsx';
import Button from '../../components/Button.jsx';
import ProductCard from '../../components/ProductCard.jsx';

// Datos de relleno solo para ver el estilo; la tienda real los pide a la API
const PLACEHOLDER = `${import.meta.env.BASE_URL}placeholder.svg`;
const SAMPLE = Array.from({ length: 6 }, (_, i) => ({
  id: i + 1,
  name: `Producto de muestra ${i + 1}`,
  price: 19.9 + i * 10,
  price_with_tax: Math.round((19.9 + i * 10) * 1.21 * 100) / 100, // solo de relleno; en la tienda real lo calcula la API
  image_url: i === 0 ? null : PLACEHOLDER, // la primera prueba el placeholder por defecto
}));

/** Página de muestra (ruta /#/muestra) con todos los componentes base. */
export default function StylePreviewPage() {
  return (
    <>
      <Container>
        <div className="hero">
          <h1 className="text-hero">Título grande, peso fuerte.</h1>
          <p className="text-muted">Texto de relleno neutro para comprobar la tipografía y el espaciado.</p>
          <div className="hero__actions">
            <Button to="/catalogo">Ver catálogo</Button>
            <Button variant="secondary" to="/soporte">Contacto</Button>
          </div>
        </div>
      </Container>

      <Section title="Productos destacados" alt>
        <div className="grid">
          {SAMPLE.map((product, index) => (
            <ProductCard key={product.id} product={product} index={index} />
          ))}
        </div>
      </Section>

      <Section title="Enlaces">
        <p>
          Un <a href="#/muestra" className="link">enlace con subrayado animado</a> dentro de un párrafo.
        </p>
      </Section>
    </>
  );
}
