<?php
/**
 * Форма отправки сообщения магазину
 * @var $this Shops
 */
?>
<div class="v-descr_contact pdt15">
    <div class="v-descr_contact__form">
        <form action="<?= Shops::urlContact($link) ?>" id="j-shop-contact-form">
            <?= Users::i()->writeForm('j-shop-contact-form') ?>
        </form>
    </div>
</div>