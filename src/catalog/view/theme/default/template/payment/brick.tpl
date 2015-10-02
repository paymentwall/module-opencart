<h2><?php echo $text_credit_card; ?></h2>
<div class="content" id="payment">
    <div id="brick-errors" style="display: none;"></div>
    <form id="brick-creditcard-form" method="post">
        <table class="form">
            <tr>
                <td><?php echo $entry_cc_number; ?></td>
                <td><input type="text" name="cc_number" id="input-cc-number" value="" data-brick="card-number"/></td>
            </tr>
            <tr>
                <td><?php echo $entry_cc_expire_date; ?></td>
                <td>
                    <select name="cc_expire_date_month" id="input-cc-expire-month" data-brick="card-expiration-month">
                        <?php foreach ($months as $month) { ?>
                        <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
                        <?php } ?>
                    </select>
                    /
                    <select name="cc_expire_date_year" id="input-cc-expire-year" data-brick="card-expiration-year">
                        <?php foreach ($year_expire as $year) { ?>
                        <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php echo $entry_cc_cvv2; ?></td>
                <td><input type="text" name="cc_cvv2" value="" size="4" id="input-cc-cvv2" data-brick="card-cvv"/></td>
            </tr>
        </table>
        <div class="buttons">
            <div class="right">
                <input type="submit" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button"/>
            </div>
        </div>

        <input type="hidden" name="cc_brick_token" id="hidden-brick-token"/>
        <input type="hidden" name="cc_brick_fingerprint" id="hidden-brick-fingerprint"/>
    </form>
</div>

<script type="text/javascript">
    function errorHandler(error) {
        $('#brick-errors').html('');
        var templError = $('#brick-error-template').html();

        if (typeof error == "string") {
            $('#brick-errors').append(templError.replace('{{#content#}}', error));
        } else if (typeof error == 'object') {
            for (it in error) {
                $('#brick-errors').append(templError.replace('{{#content#}}', error[it]));
            }
        }
        $('#brick-errors').show();
    }

    $.getScript("https://api.paymentwall.com/brick/brick.1.3.js", function (data, textStatus, jqxhr) {
        var brick = new Brick({
            public_key: '<?php echo $public_key; ?>',
            form: {formatter: true}
        }, 'custom');

        $('#brick-creditcard-form').unbind('submit.brickForm').on('submit.brickForm', function (e) {
            e.preventDefault();
            // Stop event click submit
            $('#button-confirm').attr('disabled', true);

            brick.tokenizeCard({
                card_number: $('#input-cc-number').val(),
                card_expiration_month: $('#input-cc-expire-month').val(),
                card_expiration_year: $('#input-cc-expire-year').val(),
                card_cvv: $('#input-cc-cvv2').val()
            }, function (response) {

                $('#button-confirm').attr('disabled', false);

                if (response.type == 'Error') {
                    errorHandler(response.error);
                } else {

                    $('#brick-errors').html('');
                    $('#hidden-brick-token').val(response.token);
                    $('#hidden-brick-fingerprint').val(Brick.getFingerprint());

                    $.ajax({
                        url: 'index.php?route=payment/brick/validate',
                        type: 'post',
                        data: $('#brick-creditcard-form').serialize(),
                        dataType: 'json',
                        beforeSend: function () {
                            $('#button-confirm').attr('disabled', true);
                            $('#brick-creditcard-form .alert-info').remove();
                            $('#brick-errors').hide();
                            $('#payment').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
                        },
                        complete: function () {
                            $('#button-confirm').attr('disabled', false);
                            $('.attention').remove();
                        },
                        success: function (data) {
                            $('#brick-creditcard-form .alert-info').remove();
                            if (data.status == 'success') {
                                $('#button-confirm').attr('disabled', true);
                                $('#payment').before('<div class="attention">' + data.message + '</div>');
                                // Redirect to success page
                                window.setTimeout(function () {
                                    window.location.href = data.redirect;
                                }, 1000);
                            } else {
                                errorHandler(data.message);
                            }
                        }
                    });
                }
            });
            return false;
        });
    });
</script>
<div id="brick-error-template" style="display: none">
    <div class="warning">{{#content#}}</div>
</div>
