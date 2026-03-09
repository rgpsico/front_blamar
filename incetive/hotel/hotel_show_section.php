<?php include __DIR__ . '/include/header_show.php'; ?>

<section id="hotels_result_inner">
    <div class="container">
        <div class="container_description">
            <h2><?php echo isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel'; ?></h2>

            <?php include __DIR__ . '/section/banner_show.php'; ?>
            <?php include __DIR__ . '/section/description_show.php'; ?>
            <?php include __DIR__ . '/section/rooms_show.php'; ?>
            <?php include __DIR__ . '/section/dining_show.php'; ?>
            <?php include __DIR__ . '/section/facilities_show.php'; ?>
            <?php include __DIR__ . '/section/convention_show.php'; ?>
        </div>

        <?php include __DIR__ . '/include/sidebar_show.php'; ?>
    </div>
</section>
</section>
<?php include __DIR__ . '/include/gallery_modal.php'; ?>
<?php include __DIR__ . '/include/footer_show.php'; ?>
