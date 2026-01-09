<div class="acervox-grid">
<?php while ($query->have_posts()) : $query->the_post(); ?>
    <div class="acervox-card">
        <?php the_post_thumbnail('medium'); ?>
        <h3><?php the_title(); ?></h3>
        <p><?php the_excerpt(); ?></p>
    </div>
<?php endwhile; wp_reset_postdata(); ?>
</div>
