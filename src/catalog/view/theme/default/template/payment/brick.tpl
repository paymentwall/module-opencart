<div id="brick-errors" class="alert-danger" style="display: none;"></div>
<form class="form-horizontal" id="brick-creditcard-form" method="post">
    <fieldset id="payment">
        <legend><?php echo $text_credit_card; ?></legend>
        <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>

            <div class="col-sm-6">
                <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>"
                       id="input-cc-number" class="form-control" data-brick="card-number" size="24"/>
            </div>
        </div>

        <div class="form-group required">
            <label class="col-sm-2 control-label"
                   for="input-cc-expire-date"><?php echo $entry_cc_expire_date; ?></label>

            <div class="col-sm-3">
                <select name="cc_expire_date_month" id="input-cc-expire-month" class="form-control"
                        data-brick="card-expiration-month">
                    <?php foreach ($months as $month) { ?>
                    <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-3">
                <select name="cc_expire_date_year" id="input-cc-expire-year" class="form-control"
                        data-brick="card-expiration-year">
                    <?php foreach ($year_expire as $year) { ?>
                    <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvv2; ?></label>

            <div class="col-sm-6">
                <input type="text" name="cc_cvv2" value="" placeholder="<?php echo $entry_cc_cvv2; ?>"
                       id="input-cc-cvv2" class="form-control" data-brick="card-cvv"/>
            </div>
        </div>
        <div class="buttons">
            <div class="pull-right">
                <input type="submit" value="<?php echo $button_confirm; ?>" id="button-confirm"
                       data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary"/>
            </div>
        </div>
    </fieldset>

    <input type="hidden" name="cc_brick_token" id="hidden-brick-token"/>
    <input type="hidden" name="cc_brick_fingerprint" id="hidden-brick-fingerprint"/>
    <input type="hidden" name="cc_brick_charge_id" id="hidden-brick-charge-id"/>
    <input type="hidden" name="cc_brick_secure_token" id="hidden-brick-secure-token"/>

</form>

<script type="text/javascript">
    (function ($) {
        var OCBrick = {
            data: null,
            errorHandle: function (error) {
                $('#brick-errors').html('');
                var tplError = $('#brick-error-template').html();
                if (typeof error == "string") {
                    $('#brick-errors').append(tplError.replace('{{#content#}}', error));
                }
                if (typeof error == 'object') {
                    for (it in error) {
                        $('#brick-errors').append(tplError.replace('{{#content#}}', error[it]));
                    }
                }
                $('#brick-errors').show();
            },
            successHandle: function (data) {
                $('#button-confirm').attr('disabled', true);
                $('#payment').before($('<div class="attention alert alert-success">').html(data.message));
                // Redirect to success page
                window.setTimeout(function () {
                    window.location.href = data.redirect;
                }, 1000);
            },
            threeDSecureHandle: function (data) {
                $('#button-confirm').attr('disabled', true);
                OCBrick.data = data.secure;
                var msgContainer = $('<div class="attention alert alert-info"/>');
                var clickHere = $('<a href="javascript:void(0);"/>');
                clickHere.click(OCBrick.openVerify3DSPopup).html('<?php echo $text_click_here ?>');
                msgContainer.append(data.message + '&nbsp;')
                    .append(clickHere)
                    .insertBefore('#payment');
            },
            tokenizeCallback: function (response) {
                $('#button-confirm').attr('disabled', false);
                if (response.type == 'Error') {
                    OCBrick.errorHandle(response.error);
                } else {
                    $('#brick-errors').html('');
                    $('#hidden-brick-token').val(response.token);
                    $('#hidden-brick-fingerprint').val(Brick.getFingerprint());
                    OCBrick.sendPaymentRequest();
                }
            },
            openVerify3DSPopup: function (e) {
                var win = window.open("", "Brick: Verify 3D secure", "toolbar=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=no, width=1024, height=720");
                var popup = win.document.body;
                $(popup).append(OCBrick.data);
                win.document.forms[0].submit();
                return false;
            },
            showAttentionMessage: function (message) {
                $('#payment').before('<div class="attention brick-attention alert alert-warning"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> ' + message + '</div>');
            },
            threeDSecureMessageHandle: function (event) {
                var origin = event.origin || event.originalEvent.origin;
                if (origin !== "https://api.paymentwall.com")
                    return;
                var brickData = JSON.parse(event.data);
                if (brickData && brickData.event == '3dSecureComplete') {
                    $('.attention').remove();
                    $('#hidden-brick-secure-token').val(brickData.data.secure_token);
                    $('#hidden-brick-charge-id').val(brickData.data.charge_id);
                    OCBrick.sendPaymentRequest();
                }
            },
            sendPaymentRequest: function () {
                $.ajax({
                    url: 'index.php?route=payment/brick/validate',
                    type: 'post',
                    data: $('#brick-creditcard-form').serialize(),
                    dataType: 'json',
                    beforeSend: function () {
                        $('#button-confirm').attr('disabled', true);
                        $('#brick-creditcard-form .alert-info').remove();
                        $('#brick-errors').hide();
                        OCBrick.showAttentionMessage('<?php echo $text_wait; ?>');
                    },
                    complete: function () {
                        $('#button-confirm').attr('disabled', false);
                        $('.brick-attention').remove();
                    },
                    success: function (data) {
                        $('#brick-creditcard-form .alert-info').remove();
                        if (data.status == 'success') {
                            OCBrick.successHandle(data);
                        } else if (data.status == '3ds') {
                            OCBrick.threeDSecureHandle(data);
                        } else {
                            OCBrick.errorHandle(data.message);
                        }
                    }
                });
            }
        };
        $.getScript("https://api.paymentwall.com/brick/brick.1.4.js", function (data, textStatus, jqxhr) {
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
                }, OCBrick.tokenizeCallback);
                return false;
            });
            // Add event listener for 3D-secure
            window.addEventListener("message", OCBrick.threeDSecureMessageHandle, false);
        });
    })(jQuery);
</script>
<div id="brick-error-template" style="display: none">
    <div class="alert alert-danger">
        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
        <span class="sr-only">Error:</span>
        {{#content#}}
        <button type="button" class="close" data-dismiss="alert">×</button>
    </div>
</div>