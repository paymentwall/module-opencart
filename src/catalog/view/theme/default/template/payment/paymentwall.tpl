<h2><?php echo $text_credit_card; ?></h2>
<div style="position: relative; margin-top: 20px; margin-bottom: 10px;">
    <?php print_r($url['iframe']); ?>
</div>
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