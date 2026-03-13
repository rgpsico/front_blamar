<?php
require_once __DIR__ . '/../../session_middleware.php';
// requireAuthenticatedSession();
include __DIR__ . '/include/header_show.php';
?>

<style>
    #hotels_result_inner > .container {
        display: flex;
        align-items: flex-start;
        gap: 24px;
    }

    #hotels_result_inner .container_description {
        flex: 1 1 auto;
        min-width: 0;
    }

    #hotels_result_inner .featurs_right {
        flex: 0 0 290px;
    }

    @media (max-width: 1024px) {
        #hotels_result_inner > .container {
            flex-direction: column;
        }

        #hotels_result_inner .featurs_right {
            width: 100%;
            max-width: 100%;
        }
    }
</style>

<section id="hotels_result_inner">
    <div class="container">
        <div class="container_description">
            <h2><?php echo isset($venue_name) && $venue_name !== '' ? h($venue_name) : 'Venue'; ?></h2>

            <?php include __DIR__ . '/section/banner_show.php'; ?>
            <?php include __DIR__ . '/section/description_show.php'; ?>
            <?php include __DIR__ . '/section/planta_show.php'; ?>
        </div>

        <?php include __DIR__ . '/include/sidebar_show.php'; ?>
    </div>
</section>
</section>
<?php include __DIR__ . '/include/gallery_modal.php'; ?>
<?php include __DIR__ . '/include/footer_show.php'; ?>

