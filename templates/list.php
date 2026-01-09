<?php
if (!defined('ABSPATH')) exit;
/**
 * Template de lista para itens do acervo
 * 
 * @var WP_Query $query
 */
?>
<div class="acervox-list">
<?php while ($query->have_posts()) : $query->the_post(); ?>
    <article class="acervox-list-item">
        <?php if (has_post_thumbnail()) : ?>
            <div class="acervox-list-thumbnail">
                <?php the_post_thumbnail('medium'); ?>
            </div>
        <?php endif; ?>
        
        <div class="acervox-list-content">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php if (has_excerpt()) : ?>
                <p class="acervox-list-excerpt"><?php the_excerpt(); ?></p>
            <?php endif; ?>
            
            <?php
            $collection_id = get_post_meta(get_the_ID(), '_acervox_collection', true);
            if ($collection_id) {
                $collection = get_post($collection_id);
                if ($collection) {
                    echo '<p class="acervox-list-collection"><small>Coleção: ' . esc_html($collection->post_title) . '</small></p>';
                }
            }
            ?>
        </div>
    </article>
<?php endwhile; 
wp_reset_postdata(); ?>
</div>
