// Funções usadas por todas as telas.
// Cada página .html carrega este arquivo com: <script src="js/funcoes.js"></script>

// Chama a API do PHP e devolve o JSON que ele respondeu.
// - Sem "dados": faz um GET (usado para listar e ver).
// - Com "dados": faz um POST mandando os dados em JSON (usado para criar, alterar e excluir).
// Se o PHP responder com erro, lança o erro para a tela mostrar a mensagem.
async function chamarApi(endereco, dados) {
    let opcoes = { method: "GET" };

    if (dados !== undefined) {
        opcoes = {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dados)
        };
    }

    let resposta;
    let json;

    try {
        resposta = await fetch(endereco, opcoes);
        json = await resposta.json();
    } catch (erro) {
        // Cai aqui quando o PHP não está rodando ou não devolveu JSON
        // (ex.: página aberta pelo Live Server, que não roda PHP)
        throw new Error("O PHP não respondeu. Abra o projeto por php -S localhost:8000 (não pelo Live Server).");
    }

    // resposta.ok é false quando o código HTTP é de erro (400, 404, 405...)
    if (!resposta.ok) {
        const erro = new Error(json.erro || "Erro desconhecido.");
        erro.lista = json.erros || [json.erro]; // os usuários podem ter várias mensagens
        throw erro;
    }

    return json;
}

// Mostra uma mensagem na <div id="mensagem"> da página.
// tipo: "sucesso" ou "erro" (muda a cor, ver estilo.css)
function mostrarMensagem(texto, tipo) {
    const caixa = document.getElementById("mensagem");
    caixa.className = "mensagem " + tipo;
    caixa.innerHTML = "";

    // Pode ser uma mensagem só ou uma lista de mensagens
    const lista = Array.isArray(texto) ? texto : [texto];

    for (const linha of lista) {
        const paragrafo = document.createElement("p");
        paragrafo.textContent = linha;
        caixa.appendChild(paragrafo);
    }
}

// Mostra o erro que veio do chamarApi
function mostrarErro(erro) {
    mostrarMensagem(erro.lista || erro.message, "erro");
}

// Pega um valor que veio no endereço. Ex.: ver_pergunta.html?id_pergunta=1
function pegarParametro(nome) {
    const parametros = new URLSearchParams(window.location.search);
    return parametros.get(nome) || "";
}

// Troca os caracteres especiais para o texto aparecer certo no HTML
// (faz o mesmo papel do htmlspecialchars do PHP)
function escaparHtml(texto) {
    return String(texto)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
