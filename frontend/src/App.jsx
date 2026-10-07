import { Route, Routes } from 'react-router-dom';
import Layout from './components/Layout.jsx';
import HomePage from './pages/home/HomePage.jsx';
import CatalogPage from './pages/catalog/CatalogPage.jsx';
import ProductPage from './pages/product/ProductPage.jsx';
import CheckoutPage from './pages/checkout/CheckoutPage.jsx';
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
        <Route path="checkout" element={<CheckoutPage />} />
        <Route path="admin" element={<AdminPage />} />
        <Route path="soporte" element={<SupportPage />} />
        <Route path="muestra" element={<StylePreviewPage />} />
      </Route>
    </Routes>
  );
}
