function abrirModalExcluir(id, nome) {
    document.getElementById('idExcluir').value = id;
    document.getElementById('nomeExcluir').textContent = nome;

    document.getElementById('modalExcluir').style.display = 'flex';
}

function fecharModalExcluir() {
    document.getElementById('modalExcluir').style.display = 'none';
}