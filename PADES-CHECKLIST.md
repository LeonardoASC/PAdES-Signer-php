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
16. Interoperabilidade

Somente depois do core estar estável.

## 16.1 Interoperabilidade

- [ ] Testes com Adobe Acrobat
- [ ] Testes com Adobe Reader
- [ ] Testes com pyHanko
- [ ] Testes com DSS Europeu
- [ ] Testes com validadores ETSI
- [ ] Testes de interoperabilidade internacional
- [ ] Fixtures internacionais
- [ ] PDFs de conformidade ETSI
- [ ] Compatibilidade Windows Preview
- [ ] Compatibilidade macOS Preview
17. Segurança e Performance
## 17.1 Segurança

- [ ] Streaming de PDFs grandes
- [ ] Assinatura sem carregar PDF inteiro em memória
- [ ] Proteção contra malformed PDFs
- [ ] Proteção contra object injection
- [ ] Proteção contra xref corruption
- [ ] Hardenização ASN.1 parser
- [ ] Limites de memória configuráveis
- [ ] Limites de tamanho configuráveis
- [ ] Timeout configurável para TSA/OCSP
18. Documentação e Compliance

ÚLTIMA camada.

## 18.1 Documentação

- [ ] Documentação de API PAdES
- [ ] Documentação arquitetural
- [ ] Guia de interoperabilidade
- [ ] Guia de múltiplas assinaturas
- [ ] Guia de timestamp
- [ ] Guia de LTV/LTA
- [ ] Guia de signer providers
- [ ] Mapeamento ETSI EN 319 142
- [ ] Verificação formal de conformidade ETSI
- [ ] Matriz de conformidade ETSI EN 319 142
- [ ] Matriz de conformidade ISO 32000
- [ ] Relatório ETSI detalhado
- [ ] Relatório de integridade PDF
- [ ] Relatório de cadeia criptográfica

19. Pendencias identificadas comparando com pyHanko

Esta lista separa itens que podem ja ter uma estrutura inicial no projeto,
mas ainda precisam de implementacao semantica robusta para chegar perto do
comportamento do pyHanko.

## 19.1 Validacao real de assinatura PDF

- [ ] Criar modelo equivalente a `EmbeddedPdfSignature`
- [ ] Extrair campo de assinatura e dicionario `/Sig` por parser PDF, nao por busca textual
- [ ] Extrair `/Contents` preservando bytes originais
- [ ] Extrair e validar `/ByteRange` semanticamente
- [ ] Calcular digest real dos bytes cobertos pelo `/ByteRange`
- [ ] Comparar digest calculado com `messageDigest` do CMS
- [ ] Verificar assinatura criptografica do `SignerInfo`
- [ ] Identificar certificado do assinante dentro do CMS
- [ ] Validar que o certificado do assinante corresponde ao `SignerInfo`
- [ ] Detectar se a assinatura cobre o documento inteiro ou apenas uma revisao
- [ ] Detectar bytes nao cobertos, gaps e cauda nao assinada
- [ ] Validar assinaturas em PDFs com multiplas revisoes
- [ ] Gerar status detalhado de validacao da assinatura PDF

## 19.2 Analise de revisoes e modificacoes

- [ ] Implementar politica de diff entre revisoes assinadas
- [ ] Classificar modificacoes permitidas e proibidas apos assinatura
- [ ] Validar alteracoes permitidas por DocMDP
- [ ] Validar alteracoes permitidas por FieldMDP
- [ ] Permitir updates legitimos de DSS e timestamps documentais
- [ ] Rejeitar alteracoes suspeitas em objetos assinados
- [ ] Detectar substituicao de objetos por incremental update malicioso
- [ ] Validar cobertura da xref da revisao assinada
- [ ] Gerar relatorio de integridade incremental do PDF

## 19.3 Parser PDF robusto

- [ ] Substituir regex criticas por tokenizer/parser PDF estrutural
- [ ] Resolver objetos indiretos de forma recursiva e segura
- [ ] Suportar geracoes de objetos diferentes de zero
- [ ] Suportar nomes, strings, arrays e dicionarios PDF aninhados corretamente
- [ ] Suportar streams com filtros comuns alem de FlateDecode quando necessario
- [ ] Validar xref table contra offsets reais
- [ ] Validar xref stream contra offsets reais
- [ ] Suportar object streams com parsing completo
- [ ] Suportar PDFs hibridos com xref table e xref stream
- [ ] Suportar PDFs criptografados ou rejeita-los explicitamente
- [ ] Criar limites contra PDF malformado, recursao infinita e objetos gigantes

## 19.4 CMS/CAdES/PAdES

- [ ] Validar CMS internamente sem depender apenas do comando `openssl cms`
- [ ] Implementar parser CMS completo o suficiente para validacao PAdES
- [ ] Validar `contentType`, `messageDigest` e `SigningCertificateV2`
- [ ] Validar `ESSCertIDv2` contra o certificado do assinante
- [ ] Validar `issuerSerial` contra o certificado do assinante
- [ ] Validar algoritmo de digest e assinatura conforme politica configurada
- [ ] Validar RSA-PSS, ECDSA e parametros ASN.1 corretamente
- [ ] Suportar atributos assinados opcionais sem quebrar conformidade PAdES
- [ ] Tratar `signingTime` como opcional/configuravel conforme perfil
- [ ] Criar testes comparativos com CMS gerado pelo pyHanko

## 19.5 Certificados e cadeia de confianca

- [ ] Corrigir suporte a multiplas trust anchors
- [ ] Construir cadeia X.509 por issuer/subject e Authority Key Identifier
- [ ] Validar Basic Constraints de CAs
- [ ] Validar Key Usage de CAs e certificado final
- [ ] Validar Extended Key Usage quando aplicavel
- [ ] Validar expiracao usando signing time ou timestamp confiavel
- [ ] Validar politicas de certificado quando configuradas
- [ ] Implementar path building com cadeias alternativas
- [ ] Suportar AIA fetching com cache e timeout
- [ ] Gerar resultado detalhado da cadeia de confianca

## 19.6 OCSP e CRL

- [ ] Implementar parser OCSP real
- [ ] Validar assinatura da resposta OCSP
- [ ] Validar que o responder OCSP e autorizado
- [ ] Validar `CertID` da resposta contra o certificado consultado
- [ ] Validar status good/revoked/unknown por certificado
- [ ] Validar `thisUpdate`, `nextUpdate` e `producedAt`
- [ ] Validar nonce OCSP quando enviado
- [ ] Extrair certificados embutidos em BasicOCSPResponse
- [ ] Implementar parser CRL real
- [ ] Validar assinatura da CRL
- [ ] Validar CRL issuer, AKI, nextUpdate e certificados revogados
- [ ] Integrar OCSP/CRL com validacao de cadeia

## 19.7 DSS, VRI e PAdES-LT

- [ ] Criar representacao de `DocumentSecurityStore`
- [ ] Deduplicar certificados, OCSPs e CRLs no DSS
- [ ] Registrar VRI pelo SHA-1 dos bytes reais da assinatura
- [ ] Validar que VRI aponta para material relacionado a assinatura correta
- [ ] Validar DSS sem depender apenas de presenca de chaves
- [ ] Embutir cadeia completa do assinante no DSS
- [ ] Embutir cadeia da TSA quando houver timestamp
- [ ] Embutir respostas OCSP/CRL verificadas no DSS
- [ ] Atualizar DSS por incremental update preservando revisoes anteriores
- [ ] Evitar escrever nova revisao quando DSS nao adiciona material novo
- [ ] Criar validacao offline real de PAdES-B-LT

## 19.8 Timestamp RFC 3161 e PAdES-LTA

- [ ] Validar assinatura do TimeStampToken
- [ ] Validar certificado TSA e cadeia de confianca
- [ ] Validar EKU `id-kp-timeStamping`
- [ ] Validar `messageImprint` contra assinatura ou documento correto
- [ ] Validar nonce quando aplicavel
- [ ] Validar policy OID da TSA
- [ ] Criar assinatura `/DocTimeStamp` com cobertura correta do documento
- [ ] Atualizar DSS antes/depois do timestamp conforme estrategia configurada
- [ ] Implementar cadeia de archival timestamps
- [ ] Validar ordem temporal das evidencias LTA
- [ ] Criar renovacao de evidencias criptograficas

## 19.9 Seed values, campos e aparencia

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

## 19.10 Interoperabilidade e fixtures

- [ ] Rodar validacao cruzada com pyHanko em PDFs gerados pelo PHP
- [ ] Rodar validacao cruzada do PHP em PDFs gerados pelo pyHanko
- [ ] Adicionar fixtures reais do pyHanko como testes de leitura
- [ ] Testar PDFs assinados uma vez, duas vezes e com DocTimeStamp
- [ ] Testar PDFs com xref stream, object stream e incremental updates complexos
- [ ] Testar PDFs malformados e ataques de incremental update
- [ ] Testar com Adobe Acrobat e Adobe Reader
- [ ] Testar com DSS Europeu
- [ ] Testar com validadores ETSI
- [ ] Documentar divergencias conhecidas de interoperabilidade

## 19.11 Performance e seguranca operacional

- [ ] Implementar assinatura e validacao por streaming
- [ ] Evitar carregar PDF inteiro em memoria para arquivos grandes
- [ ] Configurar limite de tamanho de PDF
- [ ] Configurar limite de tamanho de objeto PDF
- [ ] Configurar limite de profundidade de objetos aninhados
- [ ] Configurar timeout de TSA, OCSP, CRL e AIA
- [ ] Hardenizar parser ASN.1 contra entradas malformadas
- [ ] Hardenizar parser PDF contra object injection
- [ ] Remover arquivos temporarios com tratamento de erro robusto
- [ ] Evitar vazamento de material sensivel em excecoes e logs

## 19.12 Falhas atuais da suite

- [ ] Corrigir teste de CMS comparativo que espera `signingTime`
- [ ] Corrigir suporte a multiplas trust anchors em `X509TrustStoreTest`
- [ ] Reexecutar `vendor/bin/phpunit` ate a suite ficar verde
