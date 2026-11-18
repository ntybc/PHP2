# AV1 com JavaScript – Formulário Corporativo

Versão da AV1 (projeto PHP2) em que o **JavaScript envia as requisições e recebe os dados do PHP**.
O JavaScript só junta os valores da tela, manda para o PHP com `fetch` e mostra o que voltou.
Toda a regra (validação, ids, leitura e gravação dos `.txt`) continua no PHP, que agora responde só em **JSON**.

## Como rodar

1. Abra o terminal **dentro desta pasta** (no VS Code: Terminal > New Terminal).
2. Rode:
   ```
   php -S localhost:8000
   ```
3. No navegador, abra **http://localhost:8000** (abre o `index.html`).

> Não use o Live Server (porta 5500) nem abra o arquivo com duplo clique: eles não rodam PHP,
> e aí a página mostra "O PHP não respondeu...".

## Estrutura dos arquivos

```
AV1_JS/
├── index.html                  menu
├── criar_pergunta.html         1. criar pergunta de múltipla escolha (5 respostas, marca a certa)
├── criar_pergunta_texto.html   2. criar pergunta de texto (resposta de orientação opcional)
├── alterar_pergunta.html       3. alterar pergunta de múltipla escolha e as respostas
├── alterar_pergunta_texto.html 4. alterar pergunta de texto
├── listar_perguntas.html       5. listar perguntas (com Ver, Alterar e Excluir)
├── ver_pergunta.html           6. ver uma pergunta e as respostas
│                               7. excluir fica no botão Excluir da lista
├── criar_usuario.html          8. usuários: criar
├── listar_usuarios.html        8. usuários: listar e excluir
├── alterar_usuario.html        8. usuários: alterar
├── estilo.css                  o mesmo CSS da AV1 + estilos das mensagens
├── js/
│   └── funcoes.js              funções do JS usadas em todas as telas (chamarApi, mostrarMensagem...)
├── api/
│   ├── funcoes.php             funções da AV1 (ler_linhas, salvar_linhas, limpar_texto, pegar_proximo_id) + responder em JSON
│   ├── funcoes_usuarios.php    funções de usuário da AV1 (CPF, data, validar_dados_usuario)
│   ├── perguntas.php           API das perguntas
│   └── usuarios.php            API dos usuários
├── perguntas/perguntas.txt     id_pergunta;texto_pergunta;tipo_pergunta
├── respostas/respostas.txt     id_resposta;id_pergunta;texto_resposta;is_resposta_certa
└── usuarios/usuarios.txt       id_usuario;nome;data_nascimento;cpf;email;hash_da_senha
```

Os `.txt` têm o mesmo formato da AV1 (e os mesmos dados).

## Como o JavaScript conversa com o PHP

```
 [tela .html]  --- fetch (JSON) --->  [api/*.php]  --- lê/grava --->  [.txt]
      ^                                    |
      |------------ resposta JSON ---------|
```

1. O usuário preenche o formulário e clica no botão.
2. O JavaScript impede o envio normal (`evento.preventDefault()`), junta os valores num objeto
   e chama `chamarApi(...)`, que faz o `fetch` com `async/await`.
3. O PHP lê o JSON com `file_get_contents("php://input")`, valida, grava no `.txt`
   e responde em JSON, por exemplo:
   - deu certo: `{"mensagem": "Pergunta salva com sucesso! (id 4)"}`
   - deu erro: `{"erro": "Preencha a pergunta."}` com código HTTP 400 (dados errados) ou 404 (não encontrado)
4. O JavaScript lê a resposta (`resposta.json()`) e coloca na tela: a mensagem verde/vermelha,
   a lista de perguntas, a tabela de usuários ou os dados do formulário de alterar.

### Ações da API

A ação vai no endereço (`?acao=...`). Listar e ver usam **GET**; o resto usa **POST** com JSON no corpo.

| Arquivo | Ação | Método | Dados |
|---|---|---|---|
| api/perguntas.php | listar | GET | – |
| | ver | GET | `?id_pergunta=1` |
| | criar_multipla | POST | texto_pergunta, resposta_1 … resposta_5, resposta_certa (1 a 5) |
| | criar_texto | POST | texto_pergunta, texto_resposta (opcional) |
| | alterar_multipla | POST | id_pergunta + os mesmos campos do criar_multipla |
| | alterar_texto | POST | id_pergunta, texto_pergunta, texto_resposta |
| | excluir | POST | id_pergunta |
| api/usuarios.php | listar | GET | – |
| | ver | GET | `?id_usuario=1` |
| | criar | POST | nome_usuario, data_nascimento, cpf_usuario, email_usuario, senha_usuario |
| | alterar | POST | id_usuario + os mesmos campos (senha vazia mantém a atual) |
| | excluir | POST | id_usuario |

A senha é gravada só como hash (`password_hash`) e **nunca** é enviada para o JavaScript.

### Testar a API sem a tela (opcional)

```
curl "http://localhost:8000/api/perguntas.php?acao=listar"
curl -X POST -H "Content-Type: application/json" -d "{\"texto_pergunta\":\"Teste?\"}" "http://localhost:8000/api/perguntas.php?acao=criar_texto"
```
