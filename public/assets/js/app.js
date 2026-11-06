(function () {
    var toggle = document.querySelector("[data-nav-toggle]");
    var cerrar = document.querySelector("[data-nav-close]");

    function abrir(estado) {
        document.body.classList.toggle("nav-abierto", estado);
        if (toggle) {
            toggle.setAttribute("aria-expanded", estado ? "true" : "false");
        }
    }

    if (toggle) {
        toggle.addEventListener("click", function () {
            abrir(!document.body.classList.contains("nav-abierto"));
        });
    }
    if (cerrar) {
        cerrar.addEventListener("click", function () {
            abrir(false);
        });
    }

    document.querySelectorAll("[data-actividad-select]").forEach(function (select) {
        var formulario = select.closest("form");
        var tareas = formulario ? formulario.querySelector("[data-tarea-select]") : null;
        if (!tareas) {
            return;
        }
        var opciones = Array.prototype.slice.call(tareas.options);

        function filtrar() {
            var id = select.value;
            opciones.forEach(function (opcion, indice) {
                if (indice === 0) {
                    return;
                }
                var visible = id === "" || opcion.getAttribute("data-actividad") === id;
                opcion.hidden = !visible;
                opcion.disabled = !visible;
            });
            if (tareas.selectedOptions.length && tareas.selectedOptions[0].disabled) {
                tareas.value = "";
            }
        }

        select.addEventListener("change", filtrar);
        filtrar();
    });

    document.querySelectorAll("[data-tarea-form]").forEach(function (formulario) {
        var estado = formulario.querySelector('select[name="estado"]');
        var avance = formulario.querySelector('input[name="avance"]');
        if (!estado || !avance) {
            return;
        }
        estado.addEventListener("change", function () {
            if (estado.value === "Finalizada") {
                avance.value = "100";
            }
        });
    });
}());
