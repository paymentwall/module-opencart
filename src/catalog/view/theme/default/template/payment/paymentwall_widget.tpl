<?php echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content">
    <?php echo $content_top; ?>
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?>
        <a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <h1><?php echo $widget_title; ?></h1>
    <p><?php echo $widget_notice;?></p>
    <div class="paymentwall-widget">
        <?php print_r($iframe); ?>
    </div>
    <?php echo $content_bottom; ?></div>
<?php echo $footer; ?>

<?php /* Please remove comments, If you want auto redirect to the success page
<script type="text/javascript">
$(document).ready(function () {
    // Destroy interval if exist
    if (typeof paymentListener != 'undefined') {
        clearInterval(paymentListener);
    }
    paymentListener = setInterval(function () {
        $.post(
            'index.php?route=checkout/successornot',
            {
                order_id: '<?php echo $orderId;?>'
            },
            function (data) {
                if (data == 1) {
                    location.href = "index.php?route=checkout/success";
                }
            }
        );
    }, 5000);
});
</script>
*/ ?>