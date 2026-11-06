<?php

declare(strict_types=1);
?>
<section class="tarjeta">
    <h2>Actividad no disponible</h2>
    <p><?= e($aviso ?? 'No se puede mostrar esta sección.') ?></p>
    <p><a class="enlace-accion" href="<?= e(url_de('actividades')) ?>">Volver a actividades</a></p>
</section>
