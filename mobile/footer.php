<?php
/**
 * PC 端底部
 * 
 * @package wpno-vc
 */
?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <p class="copyright">
                    <?php echo wpnovc_option( 'footer_copyright', '&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. All Rights Reserved.' ); ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<?php 
$analytics = wpnovc_option( 'footer_analytics', '' );
if ( $analytics ) echo $analytics;
?>

<?php wp_footer(); ?>
</body>
</html>
