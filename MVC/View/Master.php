
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị Coolmate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/web_qlsp/Public/Css/admin_style.css">
</head>
<body>
    <?php
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $currentRoute = '';
    if (count($segments) >= 2 && $segments[0] === 'web_qlsp') {
        $currentRoute = $segments[1];
    } else {
        $currentRoute = $segments[0] ?? '';
    }
    function isActive($routes, $currentRoute) {
        if (!is_array($routes)) { $routes = [$routes]; }
        return in_array($currentRoute, $routes) ? 'active' : '';
    }
?>

<div class="sidebar">
    <div class="sidebar-brand" style="cursor: pointer;" onclick="window.location.href='/web_qlsp/overview'">
        COOLMATE<span style="color:#2f5acf">.ME</span>
    </div>
    
    <a href="/web_qlsp/overview" class="<?= isActive(['overview'], $currentRoute) ?>">
        <i class="fas fa-home"></i> Tổng quan
    </a>
    
<a href="/web_qlsp/product_list" class="<?= isActive(['product_list','product_add'], $currentRoute) ?>">
        <i class="fas fa-tshirt"></i> Sản phẩm
    </a>

    <a href="/web_qlsp/categories_list" class="<?= isActive(['categories_list'], $currentRoute) ?>">
            <i class="fas fa-list"></i> Danh mục
        </a>

    <a href="/web_qlsp/orders" class="<?= isActive(['orders'], $currentRoute) ?>">
        <i class="fas fa-shopping-bag"></i> Đơn hàng
    </a>
    
    <a href="/web_qlsp/users" class="<?= isActive(['users'], $currentRoute) ?>">
        <i class="fas fa-users"></i> Khách hàng
    </a>

    <a href="/web_qlsp/warehouse" class="<?= isActive(['warehouse'], $currentRoute) ?>">
        <i class="fa-solid fa-warehouse"></i></i> Kho hàng
    </a>

    <a href="/web_qlsp/collections" class="<?= isActive(['collections'], $currentRoute) ?>">
    <i class="fas fa-layer-group"></i> Bộ sưu tập
</a>

<a href="/web_qlsp/vouchers" class="<?= isActive(['vouchers'], $currentRoute) ?>">
    <i class="fas fa-ticket-alt"></i> Mã giảm giá
</a>


<a href="/web_qlsp/banners" class="nav-link <?= isActive(['banners'], $currentRoute) ?>">
    <div class="sb-nav-link-icon"><i class="fas fa-images"></i></div>
    Quản lý Banner
</a>

<a href="/web_qlsp/campaigns" class="nav-link <?= isActive(['campaigns'], $currentRoute) ?>">
    <div class="sb-nav-link-icon"><i class="fas fa-fire"></i></div>
    Quản lý Campaign
</a>

<a href="/web_qlsp/revenue" class="nav-link <?= isActive(['revenue'], $currentRoute) ?>">
    <div class="sb-nav-link-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    Doanh thu
</a>
<a href="/web_qlsp/reviews" class="nav-link <?= isActive(['reviews'], $currentRoute) ?>">
    <div class="sb-nav-link-icon"><i class="fa-solid fa-star"></i></div>
    Đánh giá sản phẩm
</a>
<a href="/web_qlsp/home" class="nav-link" target="_blank">
    <div class="sb-nav-link-icon"><i class="fa-solid fa-store"></i></div>
    Xem trang khách hàng
</a>

<?php if (isset($_SESSION['user_id'])): ?>
    <a href="/web_qlsp/login/logout" class="nav-link text-danger" style="margin-top: 40px;">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
    </a>
<?php endif; ?>

</div>
<div class="page">

    <header class="header">
        <h4>Quản lý hệ thống</h4>
        <div class="user-info d-flex align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-dark d-flex align-items-center">
                    <i class="fas fa-user-check fs-5 me-2 text-success"></i>
                    <span class="small fw-bold"><?php echo $_SESSION['user_name']; ?></span>
                </span>
            <?php endif; ?>
        </div>
    </header>

        <main class="content">
                <?php
                    include_once "./MVC/View/Pages/".$Page.".php";
                ?>
        </main>

    </div>

</div>


    <style>
        .header {
            position: relative !important;
            z-index: 1050 !important;
        }
        .content, .main-content, .toolbar-container, .card-table, table, tbody {
            z-index: 1 !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Tự động tìm tất cả các modal trong hệ thống và đưa ra ngoài cùng thẻ <body>
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                document.body.appendChild(modal);
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    </body>
</html>