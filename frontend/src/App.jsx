import { Route, Routes } from 'react-router-dom';
import Layout from './components/Layout.jsx';
import HomePage from './pages/home/HomePage.jsx';
import CatalogPage from './pages/catalog/CatalogPage.jsx';
import ProductPage from './pages/product/ProductPage.jsx';
import CartPage from './pages/cart/CartPage.jsx';
import CheckoutPage from './pages/checkout/CheckoutPage.jsx';
import AdminPage from './pages/admin/AdminPage.jsx';
import SupportPage from './pages/support/SupportPage.jsx';

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<HomePage />} />
        <Route path="catalogo" element={<CatalogPage />} />
        <Route path="producto/:id" element={<ProductPage />} />
        <Route path="carrito" element={<CartPage />} />
        <Route path="checkout" element={<CheckoutPage />} />
        <Route path="admin" element={<AdminPage />} />
        <Route path="soporte" element={<SupportPage />} />
      </Route>
    </Routes>
  );
}
