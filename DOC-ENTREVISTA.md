# Como eu explicaria esse projeto em uma entrevista

Este documento e um roteiro para falar sobre o projeto como uma pessoa
explicando em entrevista, sem despejar siglas sem contexto e sem parecer que eu
decorei nomes de funcoes.

## Abertura

Eu comecaria assim:

> Esse projeto e uma biblioteca em PHP para assinar PDFs digitalmente. A ideia
> foi criar uma base propria para gerar assinaturas no padrao PAdES, que e o
> padrao usado para assinatura digital em documentos PDF. Em vez de simplesmente
> chamar uma ferramenta externa e esperar o resultado, eu implementei a parte
> estrutural do PDF e a parte criptografica que monta a assinatura.

Depois eu completaria:

> O objetivo principal era conseguir pegar um PDF, preservar o arquivo original,
> adicionar uma assinatura como uma nova revisao do documento e gerar um PDF
> assinado que pudesse ser validado por leitores e validadores externos.

## Explicando o problema sem complicar

Eu falaria:

> Assinar um PDF nao e igual calcular um hash de um arquivo e salvar em algum
> lugar. O PDF tem uma estrutura interna, com objetos, catalogo, paginas,
> formularios e campos. Para assinar corretamente, eu preciso adicionar um campo
> de assinatura dentro do PDF e dizer exatamente quais partes do arquivo foram
> assinadas.

Aqui eu explicaria a ideia do `ByteRange` sem jogar a palavra solta:

> Dentro do PDF existe uma informacao chamada ByteRange. Ela basicamente diz:
> "a assinatura cobre estes intervalos de bytes do arquivo". Isso e necessario
> porque o espaco onde a propria assinatura vai ser gravada nao pode fazer parte
> do calculo da assinatura. Seria um ciclo impossivel: a assinatura dependeria
> dela mesma.

Uma forma simples de explicar:

> Entao o fluxo e: eu reservo um espaco vazio para a assinatura, calculo quais
> bytes entram na assinatura, assino esses bytes e depois preencho aquele espaco
> reservado com o resultado criptografico.

## O que significa PAdES

Eu explicaria logo, porque nao da para falar a sigla e seguir:

> PAdES significa PDF Advanced Electronic Signatures. Na pratica, e uma familia
> de regras para fazer assinatura digital dentro de PDFs de forma padronizada.
> Nao e so "colocar uma imagem de assinatura". é uma assinatura criptografica,
> com certificado digital, estrutura correta no PDF e atributos que validadores
> conseguem interpretar.

Depois:

> O projeto comeca pelo perfil base, que e o PAdES-B-B. Esse perfil ja coloca a
> assinatura criptografica no PDF com as informacoes basicas necessarias. Existem
> perfis mais avancados, como os que incluem carimbo de tempo e dados para
> validacao de longo prazo, mas eu explicaria isso depois.

## Como eu dividiria a arquitetura falando

Eu nao falaria primeiro o nome de todas as classes. Eu falaria por
responsabilidade.

> Eu dividi o projeto em algumas partes. Uma parte e a API publica, que e o que
> outro sistema chama para assinar um PDF. Outra parte cuida da estrutura do PDF:
> ler o arquivo, encontrar catalogo, paginas, campos e escrever a assinatura sem
> destruir o documento original. Outra parte cuida da criptografia: montar a
> estrutura de assinatura, usar o certificado e gerar o conteudo criptografico.
> E tem uma parte separada para credenciais, porque o certificado pode vir de um
> arquivo PFX, de PEM ou ate de um provedor externo como HSM ou assinatura
> remota.

Se pedirem mais detalhes, ai sim eu entro:

> No codigo, a classe mais simples para uso e uma API direta de assinatura. Para
> casos reais, existe uma classe mais configuravel, onde eu passo o PDF de
> entrada, o PDF de saida, a credencial e opcoes como assinatura visivel,
> carimbo de tempo, campo existente e algoritmo.

## Fluxo completo de assinatura

Eu explicaria em ordem, como se estivesse desenhando no quadro:

> Primeiro, o sistema recebe o caminho do PDF que vai ser assinado e o caminho
> onde o PDF assinado deve ser salvo. A biblioteca abre o PDF e verifica se ele
> tem uma estrutura basica valida.

> Depois ela interpreta a estrutura interna do PDF para encontrar os objetos
> principais. Ela precisa saber onde esta o catalogo do documento, onde estao as
> paginas e se ja existe um formulario de campos. Isso e importante porque a
> assinatura digital no PDF fica em um campo de assinatura.

> Em seguida, ela cria ou reutiliza um campo de assinatura. Se a assinatura for
> invisivel, esse campo existe estruturalmente, mas nao aparece para o usuario.
> Se for visivel, ela cria tambem um bloco visual no PDF, com nome do assinante,
> motivo, local e outras informacoes.

> Depois a biblioteca escreve uma nova revisao no final do PDF. Isso se chama
> atualizacao incremental. Em vez de regravar o arquivo inteiro, ela adiciona
> novos objetos no final. Isso preserva os bytes originais do documento, o que e
> muito importante em assinatura digital.

> Nesse ponto, ela ainda nao tem a assinatura final. Entao ela deixa um espaco
> reservado dentro do PDF. Com esse espaco reservado, ela calcula quais intervalos
> de bytes serao assinados.

> A partir desses bytes, ela monta a assinatura criptografica. Essa assinatura
> usa o certificado digital do usuario e gera uma estrutura chamada CMS.

Aqui eu explicaria CMS:

> CMS e a sigla para Cryptographic Message Syntax. E um formato padrao para
> empacotar assinatura digital, certificado, algoritmos usados e atributos da
> assinatura. No caso do PAdES, esse CMS fica dentro do PDF.

Depois finalizo o fluxo:

> Depois que o CMS e gerado, ele e inserido naquele espaco reservado no PDF. O
> resultado final e um PDF com a assinatura embutida, preservando o documento
> original e permitindo que validadores consigam verificar a assinatura.

## Como explicar o certificado do usuario

Eu falaria bastante disso porque e um ponto de sistema real.

> Um detalhe importante e que eu nao parti da ideia de deixar uma senha fixa em
> arquivo `.env`. Em um sistema real, cada usuario pode ter o proprio
> certificado. O usuario faz upload do certificado, normalmente um arquivo PFX
> ou P12, e digita a senha para o sistema conseguir abrir esse certificado.

Aqui eu explicaria PFX:

> PFX ou P12 e um arquivo que normalmente carrega o certificado publico, a cadeia
> de certificados e a chave privada protegida por senha. Ele e muito usado para
> certificados digitais de usuarios.

Depois:

> No fluxo que eu implementei, quando o usuario importa o certificado, a senha e
> usada apenas naquele momento para validar o arquivo e extrair metadados, como
> nome do titular, numero de serie, emissor e validade. O sistema pode salvar o
> arquivo do certificado em banco ou em storage seguro, mas nao salva a senha.

> Quando o usuario for assinar um documento depois, o sistema pede a senha de
> novo. A biblioteca abre o certificado em memoria, usa a chave privada para
> assinar e pronto. A senha continua sendo uma informacao temporaria.

Frase importante:

> A senha do certificado e tratada como dado efemero. Ela entra na operacao,
> mas nao deve ser persistida.

## Como explicar assinatura visivel

Eu diria:

> A biblioteca tambem suporta assinatura visivel. Isso nao e a assinatura
> criptografica em si, mas a representacao visual dela no PDF. Por exemplo, uma
> ultima pagina com um bloco dizendo quem assinou, motivo, local e data.

Depois:

> A parte visual e separada da validade criptografica. O que valida o documento
> e a assinatura digital embutida. A aparencia serve para o usuario enxergar no
> PDF que aquele documento foi assinado.

E completaria:

> Tambem existe a opcao de adicionar uma pagina final dedicada para assinatura,
> que e util em documentos que nao tem um espaco proprio para colocar o bloco
> visual.

## Como explicar carimbo de tempo

Eu evitaria falar "TSA" sem explicar.

> Alem da assinatura basica, existe o conceito de carimbo de tempo. Ele serve
> para provar que a assinatura existia em determinado momento, usando uma
> autoridade externa de tempo.

Agora sim:

> Essa autoridade externa e chamada de TSA, Time Stamping Authority. A biblioteca
> tem uma interface para chamar esse servico e incluir o token de tempo na
> assinatura.

Explicacao dos perfis:

> Sem carimbo de tempo, o foco e o perfil base da assinatura. Com carimbo de
> tempo, o documento se aproxima do perfil PAdES-B-T, em que o "T" vem de
> timestamp.

## Como explicar validacao de longo prazo

Aqui entram siglas como DSS, VRI, OCSP e CRL, mas eu explicaria antes o
problema.

> Um problema em assinatura digital e que o certificado pode expirar ou ser
> revogado. Entao, para validar um documento muito tempo depois, nao basta olhar
> so a assinatura. O PDF pode precisar carregar evidencias que provem que, no
> momento da assinatura, o certificado era valido.

Agora explico as siglas:

> OCSP e um protocolo para perguntar a uma autoridade certificadora se um
> certificado estava valido ou revogado. CRL e uma lista de certificados
> revogados. Os dois sao formas de obter informacao de revogacao.

> DSS, dentro do PDF, significa Document Security Store. E uma area onde o PDF
> pode guardar material de validacao, como certificados, respostas OCSP e listas
> CRL.

> VRI significa Validation Related Information. E uma estrutura que organiza
> essas evidencias para uma assinatura especifica dentro do PDF.

E eu seria honesto:

> O projeto tem estruturas iniciais e testes para essas partes, mas eu trataria
> PAdES-LT e PAdES-LTA como areas que exigem validacao mais pesada antes de
> prometer uso amplo em producao. O foco principal maduro e o fluxo base de
> assinatura.

## Como explicar seguranca

Eu falaria:

> Como estamos lidando com certificado digital, a parte de seguranca e central.
> A biblioteca nao deve salvar senha de certificado. O sistema consumidor tambem
> nao deveria gravar essa senha em banco, log ou arquivo temporario.

Pontos que eu mencionaria:

> O PFX precisa ficar fora de diretorios publicos. PDFs temporarios tambem devem
> ficar fora de `public`. Logs nao devem conter senha, chave privada, certificado
> completo ou o conteudo CMS inteiro. E o sistema precisa ter controle de acesso
> para garantir que um usuario nao use o certificado de outro.

Frase boa:

> A biblioteca fornece o mecanismo de assinatura. A politica de armazenamento,
> permissao e auditoria fica no sistema consumidor, mas a API foi pensada para
> nao obrigar ninguem a salvar senha.

## Como explicar testes

Eu falaria:

> Como esse tipo de projeto tem muito detalhe de baixo nivel, eu investi bastante
> em testes. Existem testes para montar estruturas criptograficas, calcular
> ByteRange, gerar PDF assinado, validar assinatura estruturalmente, usar PFX,
> usar PEM, assinatura visivel e partes de timestamp e validacao.

Depois:

> Tambem existem testes que dependem de ambiente externo, como certificado real
> ou validador externo. Esses testes sao pulados quando o ambiente nao tem essas
> credenciais, porque nao faria sentido colocar certificado real no repositorio.

Resultado que posso citar:

> Na ultima execucao, a suite tinha 352 testes, 1140 assertions e 20 skips por
> dependencias externas.

## Como explicar publicacao como pacote

Eu diria:

> Alem de implementar a biblioteca, eu preparei o projeto para ser consumido por
> outros sistemas via Composer. O pacote foi publicado no Packagist com o nome
> `nihillabs/pades-core`, entao outro projeto PHP consegue instalar com Composer.

Exemplo falado:

> Em qualquer sistema PHP com Composer, eu consigo rodar `composer require
> nihillabs/pades-core` e usar as classes da biblioteca pelo autoload PSR-4.

> Para facilitar uso em frameworks sem criar pacote especifico para cada um,
> tambem existe um cliente configuravel por array. Entao uma aplicacao Laravel,
> Symfony ou PHP puro pode passar sua propria configuracao e chamar a mesma API
> publica.

Isso mostra produto, nao so codigo:

> Isso foi importante porque transforma o projeto em uma biblioteca reutilizavel,
> versionada e instalavel.

## Pergunta: "qual foi a parte mais dificil?"

Eu responderia assim:

> A parte mais dificil foi fazer a estrutura PDF conversar corretamente com a
> assinatura criptografica. O PDF precisa ser alterado de forma incremental, o
> espaco da assinatura precisa ser reservado antes, o ByteRange precisa excluir
> exatamente esse espaco e o CMS precisa ser gerado em cima dos bytes corretos.
> Se qualquer offset estiver errado, o PDF ate pode abrir, mas a assinatura nao
> valida.

Se quiser aprofundar:

> Outro desafio foi separar bem as responsabilidades. A leitura e escrita de PDF
> nao deveria saber detalhes demais da chave privada, e a parte criptografica nao
> deveria depender de onde o certificado esta armazenado. Por isso existe uma
> separacao entre PDF, credenciais e assinatura criptografica.

## Pergunta: "por que fazer isso em vez de usar uma biblioteca pronta?"

Resposta equilibrada:

> Em um projeto real, se uma biblioteca madura resolve tudo, normalmente vale
> usar. Nesse caso, a proposta foi construir uma engine propria para entender e
> controlar o fluxo completo: PDF, ByteRange, CMS, certificado, assinatura
> visivel e interoperabilidade. O ganho foi controle e aprendizado profundo. O
> custo e que precisa de muitos testes e validacao externa.

## Pergunta: "isso esta pronto para producao?"

Eu seria sincero:

> Eu diria que o fluxo principal de assinatura base esta bem encaminhado e tem
> testes, mas eu teria cautela antes de prometer todos os perfis avancados de
> PAdES em producao. Assinatura digital exige interoperabilidade com validadores
> externos, politicas de certificado, autoridade de tempo e regras de revogacao.
> Entao eu separo bem o que e fluxo principal e o que ainda e evolucao.

Isso passa maturidade:

> Eu prefiro ser claro sobre limites do projeto do que vender uma conformidade
> que ainda precisa de mais validacao.

## Pergunta: "o que voce melhoraria?"

Eu responderia:

> Eu melhoraria principalmente a interoperabilidade com validadores externos,
> como Adobe, DSS europeu e pyHanko. Tambem evoluiria a validacao de longo
> prazo, com OCSP, CRL e armazenamento de evidencias no PDF. Alem disso, criaria
> exemplos de integracao em aplicacoes PHP reais e adicionaria CI publico para
> rodar a suite a cada pull request.

## Pontos que eu quero deixar claros na entrevista

Eu tentaria deixar estes pontos no ar:

- Eu entendo o problema de negocio: assinar PDF de forma verificavel.
- Eu entendo o problema tecnico: PDF, bytes, criptografia e certificado.
- Eu nao trato assinatura visivel como assinatura real.
- Eu sei que senha de certificado nao deve ser salva.
- Eu sei diferenciar fluxo base de perfis avancados.
- Eu testei bastante.
- Eu publiquei como pacote reutilizavel.
- Eu sei explicar limites e proximos passos.

## Resposta curta se pedirem para resumir tudo

> Eu desenvolvi uma biblioteca PHP para assinatura digital de PDFs no padrao
> PAdES. Ela preserva o PDF original com atualizacao incremental, calcula os
> intervalos corretos de bytes assinados, gera a estrutura criptografica CMS,
> suporta certificados PFX/P12 e PEM, permite assinatura visivel e foi pensada
> para sistemas reais onde o usuario importa o certificado e informa a senha
> apenas no momento de uso. Tambem publiquei como pacote Composer e cobri o
> projeto com testes automatizados.

## Encerramento

Se eu precisasse fechar a explicacao, eu diria:

> Para mim, o valor desse projeto foi juntar engenharia de produto com detalhe
> tecnico baixo nivel. Ele nao e so uma tela que chama uma API. Ele entra no
> formato PDF, entende como uma assinatura precisa ser encaixada, gera a parte
> criptografica e ainda considera como isso seria usado em um sistema real, com
> upload de certificado, senha efemera, armazenamento seguro e instalacao via
> Composer.
