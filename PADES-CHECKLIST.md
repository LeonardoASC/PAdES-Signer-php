1. Fundação Arquitetural
1.1 API Pública PAdES

Essas definições vêm primeiro porque impactam TODO o projeto.

# Checklist PAdES
O QUE JA FOI FEITO.
## Core PAdES PDF

- [x] Assinatura PDF-first
- [x] Incremental update básico
- [x] `/ByteRange`
- [x] `/Contents`
- [x] `/Type /Sig`
- [x] `/Filter /Adobe.PPKLite`
- [x] `/SubFilter /ETSI.CAdES.detached`
- [x] `/Extensions /ESIC`
- [x] AcroForm básico
- [x] `/SigFlags 3`
- [x] Campo `/FT /Sig`
- [x] Widget annotation
- [x] Assinatura invisível
- [x] Assinatura visível configurável
- [x] `/Rect` configurável
- [x] `/F` configurável
- [x] `/AP` opcional
- [x] Appearance stream básico
- [x] `/Name`
- [x] `/Reason`
- [x] `/Location`
- [x] `/ContactInfo`

## CMS Interno do PAdES

- [x] CMS SignedData interno
- [x] CMS detached
- [x] SignedAttributes
- [x] `contentType`
- [x] `messageDigest`
- [x] `signingTime`
- [x] `SigningCertificateV2`
- [x] `ESSCertIDv2`
- [x] `issuerSerial`
- [x] CertificateSet DER sorted
- [x] SHA-256 AlgorithmIdentifier
- [x] `SignedData` version 1
- [x] Certificados X.509 embutidos no CMS
- [x] Verificação OpenSSL do CMS

## Timestamp e LTV Inicial

- [x] RFC 3161 timestamp token como unsigned attribute
- [x] Estrutura inicial DSS
- [x] Estrutura inicial VRI
- [x] Embedding inicial de OCSP
- [x] Embedding inicial de CRL
- [x] Coleta inicial de material LTV

## Testes

- [x] Extração de assinatura do PDF
- [x] Validação básica de ByteRange
- [x] Testes estruturais PDF
- [x] Testes CMS internos
- [x] Testes de assinatura PDF real

O QUE FAZER A PARTIR DE AGORA.

## 1.1 API Pública PAdES

- [x] API pública exclusivamente PAdES
- [x] Remover CAdES standalone da API pública
- [x] Mover CMS para namespace interno
- [x] Remover ICP-Brasil do core
- [x] Remover políticas nacionais do core
- [x] Separar engine PDF da engine criptográfica
- [x] Abstração de signer provider
- [x] Interface de assinatura desacoplada
- [x] Interface de timestamp desacoplada
- [x] Interface de trust validation desacoplada
- [x] Credencial abstrata de assinatura
- [x] Signer provider plugável
2. Engine PDF Estrutural

SEM isso o projeto quebra em PDFs reais.

## 2.1 Estrutura PDF Base

- [x] Parser PDF estrutural
- [x] Parser incremental próprio
- [x] Suporte a xref table
- [x] Leitura robusta de trailer
- [x] Resolução indireta de objetos PDF
- [x] Validação completa de xref
- [x] Validação completa de trailer
- [x] Proteção contra corrupção estrutural do PDF
- [x] Preservação binária do conteúdo não assinado
- [x] Normalização de line endings do PDF
3. Compatibilidade PDF Moderna
## 3.1 PDFs Modernos

- [x] Suporte a xref stream
- [x] Suporte a object streams
- [x] Suporte a PDFs linearizados
- [x] Suporte a PDFs sem AcroForm
4. Incremental Update Real

Essa é uma das partes mais importantes do PAdES.

## 4.1 Incremental Update Robusto

- [x] Preservação robusta de revisões
- [x] Append mode rigoroso
- [x] Assinar PDFs já assinados
- [x] Detecção de assinaturas existentes
- [x] Suporte real a múltiplas assinaturas
- [x] Verificação de integridade incremental
5. AcroForm e Campos
## 5.1 AcroForm e Campos

- [x] Merge seguro de `/Fields`
- [x] Merge seguro de `/Annots`
- [x] Merge seguro de `/Extensions`
- [x] Campos de assinatura nomeáveis
- [x] Detecção de campos de assinatura vazios
- [x] Assinar campo existente
- [x] Criação automática de campo de assinatura
6. Assinaturas Avançadas PDF
## 6.1 Assinaturas PDF Avançadas

- [x] Certification signature
- [x] Approval signature
- [x] `/Perms`
- [x] DocMDP
- [x] FieldMDP
- [x] `/Reference`
- [x] `/TransformMethod`
- [x] `/TransformParams`
- [x] Controle de permissões pós-assinatura
- [x] Travamento de campos após assinatura
7. Algoritmos Criptográficos
## 7.1 Algoritmos

- [x] Algoritmos configuráveis
- [x] SHA-384
- [x] SHA-512
- [x] RSA-PSS
- [x] ECDSA
- [x] Política de algoritmo
- [x] Negotiation de algoritmo
- [x] Rejeição de algoritmos inseguros
- [x] Política mínima de hash
8. Credenciais de Assinatura
## 8.1 Credenciais

- [x] Suporte a arquivo PFX/P12
- [x] Suporte a PEM
- [x] Suporte a HSM
- [x] Suporte a PKCS#11
- [x] Suporte a smartcard
- [x] Suporte a cloud KMS
- [x] Suporte a remote signing
- [x] Callback de assinatura externa
- [x] Assinatura desacoplada do storage da chave privada
9. Cadeia e Certificados
## 9.1 Cadeia X.509

- [x] Trust store plugável
- [x] Chain validator plugável
- [x] Revocation provider plugável
- [x] Construção de cadeia X.509
- [x] Suporte a múltiplas trust chains
- [x] Suporte a AIA fetching
- [x] Cache de OCSP/CRL
10. Validação de Certificados
## 10.1 Validação Criptográfica

- [x] Validação de certificado do signatário
- [x] Validação de key usage
- [x] Validação de extended key usage
- [x] Validação de expiração
- [x] Validação temporal por signing time
- [x] Validação temporal por timestamp
11. Timestamp RFC 3161
## 11.1 Timestamp

- [x] Validação RFC 3161 completa
- [x] Validação de cadeia TSA
- [x] Validação de política TSA
- [x] PDF DocTimeStamp
12. PAdES-B-B

Primeiro perfil ETSI formal.

## 12.1 PAdES-B-B

- [x] Perfis formais PAdES Baseline
- [x] Perfil PAdES-B-B completo
- [x] Relatório PAdES por perfil
- [x] Validador PAdES-B-B
13. PAdES-B-T
## 13.1 PAdES-B-T

- [x] Perfil PAdES-B-T completo
- [x] Validador PAdES-B-T
14. DSS / VRI / LT
## 14.1 PAdES-LT

- [x] DSS completo
- [x] VRI completo
- [x] Hash VRI conforme perfil aplicável
- [x] Cadeia completa no DSS
- [x] OCSP completo no DSS
- [x] CRL completo no DSS
- [x] Inclusão automática de evidências LTV
- [x] Rebuild de DSS incremental
- [x] Validação offline futura
- [x] Perfil PAdES-B-LT completo
- [x] Validador PAdES-B-LT
15. LTA

ÚLTIMA etapa.

## 15.1 PAdES-LTA

- [x] Timestamp de documento para LTA
- [x] Renovação de evidências criptográficas
- [x] Estratégia de preservação criptográfica
- [x] Suporte a archival timestamp
- [x] Perfil PAdES-B-LTA completo
- [x] Validador PAdES-B-LTA
16. Roadmap pendente em ordem de desenvolvimento

Esta parte reorganiza somente o que ainda falta fazer. A ideia e seguir uma ordem
pratica: primeiro organizar o escopo interno, depois endurecer o core, depois
aproximar de pyHanko em validacao, LTV, LTA e interoperabilidade. A publicacao
fica para depois que o projeto estiver mais maduro.

## 16.1 Fechar escopo interno

- [x] Declarar internamente que o suporte atual principal e PAdES-B-B
- [x] Declarar internamente que PAdES-LT/LTA ainda nao deve ser considerado pronto
- [x] Documentar quais tipos de PDF sao suportados no fluxo principal
- [x] Documentar quais tipos de PDF devem ser rejeitados ou tratados como nao suportados
- [x] Separar claramente recursos prontos, experimentais e pendentes
- [x] Criar matriz simples de suporte: ITI, Adobe, DSS, pyHanko

## 16.2 Higiene do repositorio

- [x] Remover certificados reais, senhas, arquivos ICP sensiveis e PDFs privados
- [x] Garantir que fixtures publicas nao contenham dados pessoais reais
- [x] Revisar `tests/Output` e impedir commit de PDFs gerados
- [x] Revisar `.gitignore` para certificados, chaves, logs e outputs locais
- [x] Revisar mensagens de erro para nao vazar senha, caminho sensivel ou conteudo de certificado
- [x] Revisar `composer.json` para nome, descricao e autoload

## 16.3 Documentar fluxo minimo de uso

- [x] Criar exemplo minimo de assinatura com PFX/P12
- [x] Criar exemplo minimo de assinatura com PEM
- [x] Criar exemplo de assinatura visivel com pagina final dedicada
- [x] Criar exemplo de assinatura invisivel
- [x] Criar exemplo usando `Pades::sign`
- [x] Criar exemplo usando `PadesSigner` com `PadesSignatureOptions`
- [x] Documentar comando para rodar testes no Git Bash
- [x] Documentar como configurar senha de certificado por variavel de ambiente

17. Endurecer o PDF core

Antes de avancar em validacao e LTV, o parser e o incremental update precisam
ficar mais confiaveis para PDFs reais variados.

## 17.1 Parser PDF robusto

- [x] Substituir regex criticas por tokenizer/parser PDF estrutural
- [x] Resolver objetos indiretos de forma recursiva e segura
- [x] Suportar geracoes de objetos diferentes de zero
- [x] Suportar nomes, strings, arrays e dicionarios PDF aninhados corretamente
- [x] Suportar streams com filtros comuns alem de FlateDecode quando necessario
- [x] Validar xref table contra offsets reais
- [x] Validar xref stream contra offsets reais
- [x] Suportar object streams com parsing completo
- [x] Suportar PDFs hibridos com xref table e xref stream
- [x] Suportar PDFs criptografados ou rejeita-los explicitamente
- [x] Criar limites contra PDF malformado, recursao infinita e objetos gigantes

## 17.2 Pagina final de assinatura

- [x] Tornar configuravel o tamanho padrao da pagina final de assinatura
- [x] Tornar configuravel o retangulo padrao da assinatura grande
- [x] Suportar arvore `/Pages` com varios niveis ao adicionar pagina final
- [x] Suportar `/Kids` indireto ou estruturas de pagina mais complexas
- [x] Preservar recursos herdados relevantes da arvore de paginas quando necessario
- [x] Validar `/Count` de paginas antes e depois do incremental update
- [x] Criar teste com PDF de 5 paginas confirmando saida com 6 paginas
- [x] Criar teste com PDF ja assinado confirmando nova revisao sem quebrar assinatura anterior
- [x] Criar teste com AcroForm existente e pagina final dedicada

## 17.3 Aparencia visivel

- [x] Suportar texto visivel usando fonte embutida ou imagem renderizada
- [x] Detectar se o PDF original ja contem fontes nao incorporadas
- [x] Separar aviso de fonte causado pelo PDF original do aviso causado pela assinatura
- [x] Criar aparencia final com dados do signatario, data, motivo e local
- [ ] Testar aparencia em Adobe Acrobat, Adobe Reader, Chrome e Firefox
- [x] Testar pagina final em PDFs A4, carta, paisagem e PDFs com rotacao

18. Validacao real de assinatura PDF

Depois que a escrita do PDF estiver mais solida, o proximo passo e conseguir
validar internamente o que foi assinado, em vez de depender so de ITI, Adobe ou OpenSSL.

## 18.1 Extracao e validacao de assinatura

- [x] Criar modelo equivalente a `EmbeddedPdfSignature`
- [x] Extrair campo de assinatura e dicionario `/Sig` por parser PDF, nao por busca textual
- [x] Extrair `/Contents` preservando bytes originais
- [x] Extrair e validar `/ByteRange` semanticamente
- [x] Calcular digest real dos bytes cobertos pelo `/ByteRange`
- [x] Comparar digest calculado com `messageDigest` do CMS
- [x] Verificar assinatura criptografica do `SignerInfo`
- [x] Identificar certificado do assinante dentro do CMS
- [x] Validar que o certificado do assinante corresponde ao `SignerInfo`
- [x] Detectar se a assinatura cobre o documento inteiro ou apenas uma revisao
- [x] Detectar bytes nao cobertos, gaps e cauda nao assinada
- [x] Validar assinaturas em PDFs com multiplas revisoes
- [x] Gerar status detalhado de validacao da assinatura PDF

## 18.2 Analise de revisoes e modificacoes

- [x] Implementar politica de diff entre revisoes assinadas
- [x] Classificar modificacoes permitidas e proibidas apos assinatura
- [x] Validar alteracoes permitidas por DocMDP
- [x] Validar alteracoes permitidas por FieldMDP
- [x] Permitir updates legitimos de DSS e timestamps documentais
- [x] Rejeitar alteracoes suspeitas em objetos assinados
- [x] Detectar substituicao de objetos por incremental update malicioso
- [x] Validar cobertura da xref da revisao assinada
- [x] Gerar relatorio de integridade incremental do PDF

19. Validacao CMS/CAdES

Esta etapa transforma o CMS de algo gerado corretamente em algo que tambem pode
ser validado com semantica PAdES.

## 19.1 CMS interno

- [x] Validar CMS internamente sem depender apenas do comando `openssl cms`
- [x] Implementar parser CMS completo o suficiente para validacao PAdES
- [x] Validar `contentType`, `messageDigest` e `SigningCertificateV2`
- [x] Validar `ESSCertIDv2` contra o certificado do assinante
- [x] Validar `issuerSerial` contra o certificado do assinante
- [x] Validar algoritmo de digest e assinatura conforme politica configurada
- [x] Validar RSA-PSS, ECDSA e parametros ASN.1 corretamente
- [x] Suportar atributos assinados opcionais sem quebrar conformidade PAdES
- [x] Tratar `signingTime` como opcional/configuravel conforme perfil
- [x] Criar testes comparativos com CMS gerado pelo pyHanko

20. Certificados, cadeia e revogacao

So depois da validacao PDF/CMS estar confiavel faz sentido aprofundar cadeia,
OCSP e CRL, porque essas evidencias dependem da assinatura correta.

## 20.1 Cadeia X.509

- [x] Corrigir suporte a multiplas trust anchors
- [x] Construir cadeia X.509 por issuer/subject e Authority Key Identifier
- [x] Validar Basic Constraints de CAs
- [x] Validar Key Usage de CAs e certificado final
- [x] Validar Extended Key Usage quando aplicavel
- [x] Validar expiracao usando signing time ou timestamp confiavel
- [x] Validar politicas de certificado quando configuradas
- [x] Implementar path building com cadeias alternativas
- [x] Suportar AIA fetching com cache e timeout
- [x] Gerar resultado detalhado da cadeia de confianca

## 20.2 OCSP e CRL

- [x] Implementar parser OCSP real
- [x] Validar assinatura da resposta OCSP
- [x] Validar que o responder OCSP e autorizado
- [x] Validar `CertID` da resposta contra o certificado consultado
- [x] Validar status good/revoked/unknown por certificado
- [x] Validar `thisUpdate`, `nextUpdate` e `producedAt`
- [x] Validar nonce OCSP quando enviado
- [x] Extrair certificados embutidos em BasicOCSPResponse
- [x] Implementar parser CRL real
- [x] Validar assinatura da CRL
- [x] Validar CRL issuer, AKI, nextUpdate e certificados revogados
- [x] Integrar OCSP/CRL com validacao de cadeia

21. Timestamp, DSS, LT e LTA

Esta parte vem depois da cadeia e revogacao porque LT/LTA so tem valor quando as
evidencias embutidas sao verificadas de verdade.

## 21.1 Timestamp RFC 3161

- [x] Validar assinatura do TimeStampToken
- [x] Validar certificado TSA e cadeia de confianca
- [x] Validar EKU `id-kp-timeStamping`
- [x] Validar `messageImprint` contra assinatura ou documento correto
- [x] Validar nonce quando aplicavel
- [x] Validar policy OID da TSA

## 21.2 DSS e PAdES-LT

- [x] Criar representacao de `DocumentSecurityStore`
- [x] Deduplicar certificados, OCSPs e CRLs no DSS
- [x] Registrar VRI pelo SHA-1 dos bytes reais da assinatura
- [x] Validar que VRI aponta para material relacionado a assinatura correta
- [x] Validar DSS sem depender apenas de presenca de chaves
- [x] Embutir cadeia completa do assinante no DSS
- [x] Embutir cadeia da TSA quando houver timestamp
- [x] Embutir respostas OCSP/CRL verificadas no DSS
- [x] Atualizar DSS por incremental update preservando revisoes anteriores
- [x] Evitar escrever nova revisao quando DSS nao adiciona material novo
- [x] Criar validacao offline real de PAdES-B-LT

## 21.3 PAdES-LTA

- [x] Criar assinatura `/DocTimeStamp` com cobertura correta do documento
- [x] Atualizar DSS antes/depois do timestamp conforme estrategia configurada
- [x] Implementar cadeia de archival timestamps
- [x] Validar ordem temporal das evidencias LTA
- [x] Criar renovacao de evidencias criptograficas

22. Campos, seed values e formularios

Esses itens sao importantes para PDFs corporativos com campos existentes, mas
podem vir depois do core de validacao e LTV.

## 22.1 Seed values, campos e locks

- [ ] Ler e respeitar Seed Value Dictionary de campos existentes
- [ ] Validar filtros e subfiltros exigidos pelo campo
- [ ] Validar algoritmos exigidos pelo campo
- [ ] Validar motivos permitidos pelo campo
- [ ] Validar certificados permitidos pelo campo
- [ ] Implementar locks de campo conforme FieldMDP
- [ ] Validar campos vazios e assinados em AcroForm hierarquico
- [ ] Suportar nomes de campo totalmente qualificados
- [ ] Melhorar appearance stream para PDFs reais
- [ ] Preservar Annots e Fields existentes sem sobrescrever indevidamente

23. Interoperabilidade e fixtures

Agora entram testes cruzados. Eles devem validar o que foi implementado nas
etapas anteriores, nao substituir validacao interna.

## 23.1 Validadores e leitores externos

- [ ] Testes com Adobe Acrobat
- [ ] Testes com Adobe Reader
- [ ] Testes com pyHanko
- [ ] Testes com DSS Europeu
- [ ] Testes com validadores ETSI
- [ ] Testes de interoperabilidade internacional
- [ ] Compatibilidade Windows Preview
- [ ] Compatibilidade macOS Preview
- [ ] Rodar validacao cruzada com pyHanko em PDFs gerados pelo PHP
- [ ] Rodar validacao cruzada do PHP em PDFs gerados pelo pyHanko
- [ ] Documentar divergencias conhecidas de interoperabilidade

## 23.2 Fixtures de conformidade

- [ ] Fixtures internacionais
- [ ] PDFs de conformidade ETSI
- [ ] Adicionar fixtures reais do pyHanko como testes de leitura
- [ ] Testar PDFs assinados uma vez, duas vezes e com DocTimeStamp
- [ ] Testar PDFs com xref stream, object stream e incremental updates complexos
- [ ] Testar PDFs malformados e ataques de incremental update

24. Seguranca e performance operacional

Quando o comportamento ja estiver correto, endurecer para uso em arquivos grandes
e entradas hostis.

## 24.1 Performance

- [ ] Implementar assinatura e validacao por streaming
- [ ] Evitar carregar PDF inteiro em memoria para arquivos grandes
- [ ] Limites de memoria configuraveis
- [ ] Limites de tamanho configuraveis
- [ ] Configurar limite de tamanho de PDF
- [ ] Configurar limite de tamanho de objeto PDF
- [ ] Configurar limite de profundidade de objetos aninhados

## 24.2 Seguranca

- [ ] Protecao contra malformed PDFs
- [ ] Protecao contra object injection
- [ ] Protecao contra xref corruption
- [ ] Hardenizar parser ASN.1 contra entradas malformadas
- [ ] Hardenizar parser PDF contra object injection
- [ ] Configurar timeout de TSA, OCSP, CRL e AIA
- [ ] Remover arquivos temporarios com tratamento de erro robusto
- [ ] Evitar vazamento de material sensivel em excecoes e logs

25. Documentacao e compliance

Ultima camada: documentar o que existe, provar conformidade e manter verificacao
continua. A publicacao fica fora deste ciclo inicial.

## 25.1 Documentacao

- [ ] Documentacao de API PAdES
- [ ] Documentacao arquitetural
- [ ] Guia de interoperabilidade
- [ ] Guia de multiplas assinaturas
- [ ] Guia de timestamp
- [ ] Guia de LTV/LTA
- [ ] Guia de signer providers
- [ ] Mapeamento ETSI EN 319 142
- [ ] Verificacao formal de conformidade ETSI
- [ ] Matriz de conformidade ETSI EN 319 142
- [ ] Matriz de conformidade ISO 32000
- [ ] Relatorio ETSI detalhado
- [ ] Relatorio de integridade PDF
- [ ] Relatorio de cadeia criptografica

## 25.2 Qualidade continua

- [ ] Configurar CI para PHP 8.2, 8.3 e 8.4
- [ ] Rodar suite completa em ambiente limpo sem arquivos locais secretos
- [ ] Rodar assinatura ICP real fora do CI e registrar resultado manual
- [ ] Criar checklist manual de validacao no ITI
- [ ] Criar checklist manual de validacao no Adobe
