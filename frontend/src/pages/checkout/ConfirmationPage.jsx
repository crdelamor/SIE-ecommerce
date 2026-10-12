import { useParams } from 'react-router-dom';

export default function ConfirmationPage() {
  const { code } = useParams();
  return <h1>Pedido {code}</h1>;
}
