import Button from '../../components/Button.jsx';

/** "Anterior / Página X de Y / Siguiente" para las listas de la administración. */
export default function AdminPagination({ page, totalPages, onChange, disabled = false, label }) {
  if (totalPages <= 1) return null;

  return (
    <nav className="admin-pagination" aria-label={label}>
      <Button variant="secondary" onClick={() => onChange(page - 1)} disabled={page <= 1 || disabled}>
        Anterior
      </Button>
      <span>
        Página {page} de {totalPages}
      </span>
      <Button variant="secondary" onClick={() => onChange(page + 1)} disabled={page >= totalPages || disabled}>
        Siguiente
      </Button>
    </nav>
  );
}
