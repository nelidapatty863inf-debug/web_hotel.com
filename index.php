<?php
/**
 * PÁGINA PRINCIPAL DEL HOTEL - VERSIÓN MEJORADA CON IMÁGENES REALES
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Obtener parámetros de búsqueda
$check_in = $_GET['check_in'] ?? date('Y-m-d', strtotime('+1 day'));
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+3 days'));
$adults = intval($_GET['adults'] ?? 2);
$children = intval($_GET['children'] ?? 0);

// Validar fechas
$dateValidation = validateDates($check_in, $check_out);
if (!$dateValidation['valid']) {
    $check_in = date('Y-m-d', strtotime('+1 day'));
    $check_out = date('Y-m-d', strtotime('+3 days'));
    $nights = 2;
} else {
    $nights = $dateValidation['nights'];
}

// Obtener habitaciones disponibles
$available_rooms = getAvailableRooms($conn, $check_in, $check_out, $adults);

// Obtener promociones destacadas
$sql = "SELECT r.*, rc.name as category_name 
        FROM rooms r 
        LEFT JOIN room_categories rc ON r.category_id = rc.id 
        WHERE r.status = 'active' 
        ORDER BY rc.sort_order, r.price_per_night 
        LIMIT 3";
$featured_rooms = $conn->query($sql);

// Array de imágenes reales para cada tipo de habitación
$room_images = [
    'Double King Suites' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&h=600&fit=crop',
    'Double Queen Suites' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&h=600&fit=crop',
    'Double Queen Suite' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&h=600&fit=crop',
    'Single King Suites' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&h=600&fit=crop',
    'Single Queen Suites' => 'https://images.unsplash.com/photo-1631049032048-f8c4b6e3f6b4?w=800&h=600&fit=crop'
];

$default_image = 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&h=600&fit=crop';

$testimonials = [
    ['name' => 'María González', 'text' => 'Excelente servicio, las habitaciones son increíbles. Volveré sin duda.', 'rating' => 5, 'image' => 'https://randomuser.me/api/portraits/women/1.jpg'],
    ['name' => 'Carlos Rodríguez', 'text' => 'La mejor experiencia de hotel que he tenido. Muy recomendado.', 'rating' => 5, 'image' => 'https://randomuser.me/api/portraits/men/1.jpg'],
    ['name' => 'Ana Martínez', 'text' => 'Atención impecable, instalaciones de primera clase.', 'rating' => 5, 'image' => 'https://randomuser.me/api/portraits/women/2.jpg']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Hotel de Lujo</title>
    <meta name="description" content="Disfruta de una experiencia única en <?= SITE_NAME ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        /* Header */
        header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e74c3c;
        }

        .btn-register, .admin-link {
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .btn-register { background: #e74c3c; }
        .admin-link { background: #3498db; }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=1920') center/cover;
            opacity: 0.3;
            animation: zoom 20s ease infinite;
        }

        @keyframes zoom {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero h1 { font-size: 3.5rem; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .hero p { font-size: 1.2rem; margin-bottom: 1.5rem; }

        .btn-hero {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-hero:hover {
            background: #c0392b;
            transform: translateY(-3px);
        }

        /* Buscador */
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin: -2rem auto 2rem;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .search-section h2 { margin-bottom: 1.5rem; color: #2c3e50; }

        .search-form .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 150px;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .btn-search {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-search:hover { transform: scale(1.02); }

        /* Contenedor */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Habitaciones */
        .rooms-section { padding: 3rem 0; }
        .rooms-section h2 { font-size: 2rem; margin-bottom: 2rem; color: #2c3e50; text-align: center; }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .room-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .room-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .room-card:hover .room-image img {
            transform: scale(1.1);
        }

        .room-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #e74c3c;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 1;
        }

        .room-info { padding: 1.5rem; }
        .room-info h3 { font-size: 1.25rem; margin-bottom: 0.75rem; color: #2c3e50; }

        .room-features {
            display: flex;
            gap: 1rem;
            margin: 0.75rem 0;
            font-size: 0.85rem;
            color: #666;
            flex-wrap: wrap;
        }

        .room-features span {
            background: #f0f0f0;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
        }

        .room-info p { color: #666; margin: 0.75rem 0; }

        .room-price {
            margin: 1rem 0;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .price { font-size: 1.75rem; font-weight: bold; color: #e74c3c; }
        .per-night { font-size: 0.85rem; color: #666; }
        .room-total { font-size: 0.9rem; color: #555; margin: 0.5rem 0; }

        .btn-view {
            display: block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem;
            text-decoration: none;
            border-radius: 25px;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-view:hover { transform: scale(1.02); }

        /* Servicios */
        .amenities-section {
            padding: 3rem 0;
            background: white;
        }

        .amenities-section h2 { text-align: center; font-size: 2rem; margin-bottom: 2rem; color: #2c3e50; }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .amenity { padding: 1.5rem; transition: transform 0.3s; }
        .amenity:hover { transform: translateY(-5px); }
        .amenity-icon { font-size: 3rem; margin-bottom: 1rem; }
        .amenity h3 { margin-bottom: 0.5rem; color: #2c3e50; }
        .amenity p { color: #666; }

        /* Testimonios */
        .testimonials-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 4rem 0;
        }

        .testimonials-section h2 { text-align: center; font-size: 2rem; margin-bottom: 2rem; color: #2c3e50; }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .testimonial-card:hover { transform: translateY(-5px); }

        .testimonial-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid #667eea;
        }

        .testimonial-card .rating { color: #f1c40f; font-size: 1.2rem; margin-bottom: 1rem; }
        .testimonial-card p { font-style: italic; color: #555; margin-bottom: 1rem; }
        .testimonial-card h4 { color: #2c3e50; }

        /* Newsletter */
        .newsletter-section {
            background: linear-gradient(135deg, #2c3e50, #1a252f);
            color: white;
            text-align: center;
            padding: 4rem 2rem;
        }

        .newsletter-section h2 { font-size: 2rem; margin-bottom: 1rem; }

        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 2rem auto 0;
            gap: 1rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
        }

        .newsletter-form button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .newsletter-form button:hover { background: #c0392b; transform: scale(1.02); }

        /* Footer */
        footer {
            background: #1a252f;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-col h3 { margin-bottom: 1rem; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.5rem; }
        .footer-col a { color: #bdc3c7; text-decoration: none; transition: color 0.3s; }
        .footer-col a:hover { color: #e74c3c; }

        .social-links { display: flex; gap: 1rem; }
        .social-links a {
            background: rgba(255,255,255,0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .social-links a:hover { background: #e74c3c; transform: translateY(-3px); }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 1rem; }
            .hero h1 { font-size: 2rem; }
            .search-form .form-row { flex-direction: column; }
            .rooms-grid { grid-template-columns: 1fr; }
            .newsletter-form { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .social-links { justify-content: center; }
        }

        @media (max-width: 480px) {
            .container { padding: 0 1rem; }
            .hero h1 { font-size: 1.5rem; }
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo"><h1>🏨 <?= SITE_NAME ?></h1></div>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="#rooms">Habitaciones</a></li>
                <li><a href="#amenities">Servicios</a></li>
                <li><a href="#testimonials">Opiniones</a></li>
                <li><a href="my_bookings.php">Mis Reservas</a></li>
                <?php if(isLoggedIn()): ?>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Carrito</a></li>
                    <li><a href="admin/logout.php">Cerrar Sesión</a></li>
                    <?php if(isAdmin()): ?>
                        <li><a href="admin/dashboard.php" class="admin-link">Panel Admin</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="admin/login.php">Iniciar Sesión</a></li>
                    <li><a href="register.php" class="btn-register">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Bienvenido a <?= SITE_NAME ?></h1>
            <p>Disfruta de una experiencia única de lujo y confort en el corazón de la ciudad</p>
            <a href="#search" class="btn-hero">Reservar Ahora</a>
        </div>
    </section>

    <!-- Buscador -->
    <section id="search" class="search-section">
        <div class="container">
            <h2>Buscar Disponibilidad</h2>
            <form id="searchForm" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Check-in</label>
                        <input type="date" name="check_in" id="check_in" value="<?= $check_in ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Check-out</label>
                        <input type="date" name="check_out" id="check_out" value="<?= $check_out ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Adultos</label>
                        <select name="adults" id="adults">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?= $i ?>" <?= $adults == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-child"></i> Niños</label>
                        <select name="children" id="children">
                            <?php for($i=0; $i<=5; $i++): ?>
                                <option value="<?= $i ?>" <?= $children == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-search"><i class="fas fa-search"></i> Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Habitaciones Destacadas -->
    <section id="featured" class="rooms-section">
        <div class="container">
            <h2>🏆 Habitaciones Destacadas</h2>
            <div class="rooms-grid">
                <?php while($room = $featured_rooms->fetch_assoc()): 
                    $total = $room['price_per_night'] * $nights;
                    $img = $room_images[$room['name']] ?? $default_image;
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($room['name']) ?>" loading="lazy">
                        <div class="room-category"><?= $room['category_name'] ?></div>
                    </div>
                    <div class="room-info">
                        <h3><?= htmlspecialchars($room['name']) ?></h3>
                        <div class="room-features">
                            <span><i class="fas fa-users"></i> <?= $room['capacity'] ?> pers</span>
                            <span><i class="fas fa-bed"></i> <?= $room['bed_type'] ?></span>
                            <span><i class="fas fa-expand"></i> <?= $room['size_sqm'] ?>m²</span>
                        </div>
                        <p><?= substr(htmlspecialchars($room['description']), 0, 100) ?>...</p>
                        <div class="room-price">
                            <span class="price"><?= formatPrice($room['price_per_night']) ?></span>
                            <span class="per-night">/ noche</span>
                        </div>
                        <div class="room-total">Total: <?= formatPrice($total) ?></div>
                        <a href="room_details.php?id=<?= $room['id'] ?>&check_in=<?= $check_in ?>&check_out=<?= $check_out ?>" class="btn-view">Ver Detalles</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Todas las Habitaciones -->
    <section id="rooms" class="rooms-section" style="background: #f8f9fa;">
        <div class="container">
            <h2>🏨 Todas Nuestras Habitaciones</h2>
            <div class="rooms-grid">
                <?php 
                $available_rooms->data_seek(0);
                while($room = $available_rooms->fetch_assoc()): 
                    $total = $room['price_per_night'] * $nights;
                    $img = $room_images[$room['name']] ?? $default_image;
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($room['name']) ?>" loading="lazy">
                        <div class="room-category"><?= $room['category_name'] ?></div>
                    </div>
                    <div class="room-info">
                        <h3><?= htmlspecialchars($room['name']) ?></h3>
                        <div class="room-features">
                            <span><i class="fas fa-users"></i> <?= $room['capacity'] ?> pers</span>
                            <span><i class="fas fa-bed"></i> <?= $room['bed_type'] ?></span>
                            <span><i class="fas fa-expand"></i> <?= $room['size_sqm'] ?>m²</span>
                        </div>
                        <p><?= substr(htmlspecialchars($room['description']), 0, 100) ?>...</p>
                        <div class="room-price">
                            <span class="price"><?= formatPrice($room['price_per_night']) ?></span>
                            <span class="per-night">/ noche</span>
                        </div>
                        <div class="room-total">Total: <?= formatPrice($total) ?></div>
                        <a href="room_details.php?id=<?= $room['id'] ?>&check_in=<?= $check_in ?>&check_out=<?= $check_out ?>" class="btn-view">Ver Detalles</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if($available_rooms->num_rows == 0): ?>
                    <div class="no-results" style="text-align: center; grid-column: 1/-1; padding: 3rem;">
                        <p>No hay habitaciones disponibles para las fechas seleccionadas.</p>
                        <p>Por favor, prueba con otras fechas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section id="amenities" class="amenities-section">
        <div class="container">
            <h2>✨ Servicios e Instalaciones</h2>
            <div class="amenities-grid">
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-swimming-pool"></i></div><h3>Piscina</h3><p>Climatizada con vista panorámica</p></div>
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-utensils"></i></div><h3>Restaurante</h3><p>Gourmet internacional 24/7</p></div>
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-spa"></i></div><h3>Spa</h3><p>Tratamientos de relajación</p></div>
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-dumbbell"></i></div><h3>Gimnasio</h3><p>Equipo profesional</p></div>
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-wifi"></i></div><h3>WiFi Gratuito</h3><p>Alta velocidad</p></div>
                <div class="amenity"><div class="amenity-icon"><i class="fas fa-concierge-bell"></i></div><h3>Room Service</h3><p>24 horas</p></div>
            </div>
        </div>
    </section>

    <!-- Testimonios -->
    <section id="testimonials" class="testimonials-section">
        <div class="container">
            <h2>⭐ Lo Que Dicen Nuestros Huéspedes</h2>
            <div class="testimonials-grid">
                <?php foreach($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <img src="<?= $testimonial['image'] ?>" alt="<?= $testimonial['name'] ?>" class="testimonial-img">
                    <div class="rating">★★★★★</div>
                    <p>"<?= $testimonial['text'] ?>"</p>
                    <h4>- <?= $testimonial['name'] ?></h4>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <h2>📧 Suscríbete a Nuestro Newsletter</h2>
            <p>Recibe ofertas exclusivas y novedades directamente en tu correo</p>
            <form class="newsletter-form" method="POST">
                <input type="email" placeholder="Tu correo electrónico" required>
                <button type="submit">Suscribirse</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><?= SITE_NAME ?></h3>
                    <p><?= SITE_ADDRESS ?></p>
                    <p>📞 <?= SITE_PHONE ?></p>
                    <p>✉️ <?= SITE_EMAIL ?></p>
                </div>
                <div class="footer-col">
                    <h3>Enlaces Rápidos</h3>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="#rooms">Habitaciones</a></li>
                        <li><a href="#amenities">Servicios</a></li>
                        <li><a href="my_bookings.php">Mis Reservas</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Políticas</h3>
                    <ul>
                        <li><a href="#">Términos y Condiciones</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                        <li><a href="#">Política de Cancelación</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Síguenos</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const params = new URLSearchParams({
                check_in: document.getElementById('check_in').value,
                check_out: document.getElementById('check_out').value,
                adults: document.getElementById('adults').value,
                children: document.getElementById('children').value
            });
            window.location.href = 'index.php?' + params;
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>