<?php
/**
 * Template para exibir um item individual do acervo
 * Compatível com Elementor
 */

if (!defined('ABSPATH')) exit;

get_header();

$item_id = get_the_ID();
$collection_id = get_post_meta($item_id, '_acervox_collection', true);
$collection = $collection_id ? get_post($collection_id) : null;

// Metadados padrão
$default_description = get_post_meta($item_id, '_acervox_default_description', true);
if (!$default_description) {
    $default_description = get_the_excerpt();
}

// Metadados personalizados
$meta_fields = [];
if ($collection_id && class_exists('AcervoX_Meta_Registry')) {
    $fields = AcervoX_Meta_Registry::get_fields($collection_id);
    foreach ($fields as $field) {
        $key = $field['key'] ?? '';
        $value = get_post_meta($item_id, '_acervox_' . $key, true);
        if ($value) {
            $meta_fields[] = [
                'label' => $field['label'] ?? $key,
                'value' => $value,
                'type' => $field['type'] ?? 'text'
            ];
        }
    }
}

// Thumbnail
$thumbnail_url = get_the_post_thumbnail_url($item_id, 'full');
?>

<div class="acervox-single-item">
    <div class="acervox-single-container">
        <?php if ($thumbnail_url) : ?>
            <div class="acervox-single-image">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>" />
            </div>
        <?php endif; ?>

        <div class="acervox-single-content">
            <header class="acervox-single-header">
                <?php if ($collection) : ?>
                    <div class="acervox-single-collection">
                        <span class="acervox-badge"><?php echo esc_html($collection->post_title); ?></span>
                    </div>
                <?php endif; ?>
                
                <h1 class="acervox-single-title"><?php the_title(); ?></h1>
                
                <div class="acervox-single-meta">
                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                        <?php echo esc_html(get_the_date()); ?>
                    </time>
                    <?php if (get_the_modified_date() !== get_the_date()) : ?>
                        <span class="acervox-meta-separator">•</span>
                        <span>Atualizado em <?php echo esc_html(get_the_modified_date()); ?></span>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($default_description) : ?>
                <div class="acervox-single-description">
                    <h2>Descrição</h2>
                    <p><?php echo wp_kses_post(nl2br($default_description)); ?></p>
                </div>
            <?php endif; ?>

            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <?php if (get_the_content()) : ?>
                    <div class="acervox-single-content-text">
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>
            <?php endwhile; endif; ?>

            <?php if (!empty($meta_fields)) : ?>
                <div class="acervox-single-metadata">
                    <h2>Metadados</h2>
                    <div class="acervox-metadata-grid">
                        <?php foreach ($meta_fields as $meta) : ?>
                            <div class="acervox-metadata-item">
                                <strong><?php echo esc_html($meta['label']); ?>:</strong>
                                <span><?php echo esc_html($meta['value']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.acervox-single-item {
    max-width: 1200px;
    margin: 0 auto;
    padding: 48px 24px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.acervox-single-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: start;
}

.acervox-single-image {
    position: sticky;
    top: 100px;
}

.acervox-single-image img {
    width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.acervox-single-content {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.acervox-single-header {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.acervox-badge {
    display: inline-block;
    padding: 6px 12px;
    background: hsl(222.2 47.4% 11.2%);
    color: white;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    width: fit-content;
}

.acervox-single-title {
    font-size: 36px;
    font-weight: 700;
    line-height: 1.2;
    margin: 0;
    color: hsl(222.2 84% 4.9%);
}

.acervox-single-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: hsl(215.4 16.3% 46.9%);
}

.acervox-meta-separator {
    color: hsl(214.3 31.8% 91.4%);
}

.acervox-single-description,
.acervox-single-content-text,
.acervox-single-metadata {
    padding-top: 24px;
    border-top: 1px solid hsl(214.3 31.8% 91.4%);
}

.acervox-single-description h2,
.acervox-single-metadata h2 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 16px 0;
    color: hsl(222.2 84% 4.9%);
}

.acervox-single-description p {
    font-size: 16px;
    line-height: 1.7;
    color: hsl(222.2 84% 4.9%);
}

.acervox-metadata-grid {
    display: grid;
    gap: 16px;
}

.acervox-metadata-item {
    padding: 16px;
    background: hsl(210 40% 96.1%);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.acervox-metadata-item strong {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: hsl(215.4 16.3% 46.9%);
}

.acervox-metadata-item span {
    font-size: 15px;
    font-weight: 500;
    color: hsl(222.2 84% 4.9%);
}

@media (max-width: 968px) {
    .acervox-single-container {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    
    .acervox-single-image {
        position: relative;
        top: 0;
    }
    
    .acervox-single-title {
        font-size: 28px;
    }
}
</style>

<?php get_footer(); ?>
