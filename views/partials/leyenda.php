<?php

declare(strict_types=1);
?>
<ul class="leyenda">
    <li><span class="sem sem-verde">En plazo</span> Primer vencimiento: transcurrió menos del 50 % y faltan más de cinco días.</li>
    <li><span class="sem sem-amarillo">Avance del plazo</span> Primer vencimiento: transcurrió el 50 % o más y faltan más de cinco días.</li>
    <li><span class="sem sem-amarillo sem-prorroga">Prórroga vigente</span> Hay un vencimiento adicional y todavía no se incumplió.</li>
    <li><span class="sem sem-rojo">Próxima a vencer</span> Primer vencimiento: faltan cinco días o menos.</li>
    <li><span class="sem sem-rojo sem-incumplido">Vencimiento incumplido</span> La fecha exigible ya pasó.</li>
    <li><span class="sem sem-cumplida">Cumplimentada</span> Deja de generar alertas.</li>
</ul>
<p class="nota-semaforo">La finalización prevista de la actividad es independiente del vencimiento de cada tarea. Una prórroga vigente no se muestra en verde ni pasa a rojo solo por quedar a cinco días.</p>
