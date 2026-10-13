import { Route, Routes } from 'react-router-dom';
import Layout from './components/Layout.jsx';
import HomePage from './pages/home/HomePage.jsx';
import CatalogPage from './pages/catalog/CatalogPage.jsx';
import ProductPage from './pages/product/ProductPage.jsx';
import CheckoutLayout from './pages/checkout/CheckoutLayout.jsx';
import CheckoutPage from './pages/checkout/CheckoutPage.jsx';
import ConfirmationPage from './pages/checkout/ConfirmationPage.jsx';
import AdminPage from './pages/admin/AdminPage.jsx';
import SupportPage from './pages/support/SupportPage.jsx';
import StylePreviewPage from './pages/home/StylePreviewPage.jsx';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<HomePage />} />
        <Route path="catalogo" element={<CatalogPage />} />
        <Route path="producto/:id" element={<ProductPage />} />
        <Route path="admin" element={<AdminPage />} />
        <Route path="soporte" element={<SupportPage />} />
        <Route path="muestra" element={<StylePreviewPage />} />
      </Route>

      {/* El checkout tiene su propia cabecera, sin menú */}
      <Route element={<CheckoutLayout />}>
        <Route path="checkout" element={<CheckoutPage />} />
        <Route path="pedido/:code" element={<ConfirmationPage />} />
      </Route>
    </Routes>
  );
}
