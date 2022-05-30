{*
 * 2008 - 2022 Wasa Kredit B2B
 *
 * MODULE Wasa Kredit
 *
 * @version   1.0.0
 * @author    Wasa Kredit AB
 * @link      http://www.wasakredit.se
 * @copyright Copyright (c) permanent, Wasa Kredit B2B
 * @license   Wasa Kredit B2B
*}

<section>
    {if $error}
        <div class="alert alert-info">
            {$response}
        </div>
    {else}
        {$iframe nofilter}

        <script>
            window.wasaCheckout.init({
                onComplete: function(orderReferences) {
                    $.ajax({
                        type: 'POST',
                        url: '{$confirm_url}',
                        data: {
                            data: orderReferences,
                        },
                        success: function(response) {
                            if (response.success == false && response.redirect) {
                                window.location.href = response.redirect;
                            }
                        }
                    });
                },
                onCancel: function(orderReferences) {
                    window.location.href = '/index.php?controller=order&step=1';
                }
            });
        </script>
    {/if}
</section>
