<?php
declare(strict_types=1);

/**
 * Rutas de administración (C). Todas las /admin/* llaman primero a requireAdmin().
 *
 *   POST  /auth/login
 *   POST  /auth/logout
 *   GET   /admin/orders?status=
 *   GET   /admin/orders/{id}
 *   PATCH /admin/orders/{id}/status
 *   GET   /admin/support
 */
