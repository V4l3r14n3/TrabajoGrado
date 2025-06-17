function mostrarSeccion(id) {
    document.querySelectorAll('.tab-content').forEach(seccion => {
        seccion.classList.remove('active');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    document.getElementById(id).classList.add('active');
    document.querySelector(`button[onclick="mostrarSeccion('${id}')"]`).classList.add('active');
}
