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

- [ ] Parser PDF estrutural
- [ ] Parser incremental próprio
- [ ] Suporte a xref table
- [ ] Leitura robusta de trailer
- [ ] Resolução indireta de objetos PDF
- [ ] Validação completa de xref
- [ ] Validação completa de trailer
- [ ] Proteção contra corrupção estrutural do PDF
- [ ] Preservação binária do conteúdo não assinado
- [ ] Normalização de line endings do PDF
3. Compatibilidade PDF Moderna
## 3.1 PDFs Modernos

- [ ] Suporte a xref stream
- [ ] Suporte a object streams
- [ ] Suporte a PDFs linearizados
- [ ] Suporte a PDFs sem AcroForm
4. Incremental Update Real

Essa é uma das partes mais importantes do PAdES.

## 4.1 Incremental Update Robusto

- [ ] Preservação robusta de revisões
- [ ] Append mode rigoroso
- [ ] Assinar PDFs já assinados
- [ ] Detecção de assinaturas existentes
- [ ] Suporte real a múltiplas assinaturas
- [ ] Verificação de integridade incremental
5. AcroForm e Campos
## 5.1 AcroForm e Campos

- [ ] Merge seguro de `/Fields`
- [ ] Merge seguro de `/Annots`
- [ ] Merge seguro de `/Extensions`
- [ ] Campos de assinatura nomeáveis
- [ ] Detecção de campos de assinatura vazios
- [ ] Assinar campo existente
- [ ] Criação automática de campo de assinatura
6. Assinaturas Avançadas PDF
## 6.1 Assinaturas PDF Avançadas

- [ ] Certification signature
- [ ] Approval signature
- [ ] `/Perms`
- [ ] DocMDP
- [ ] FieldMDP
- [ ] `/Reference`
- [ ] `/TransformMethod`
- [ ] `/TransformParams`
- [ ] Controle de permissões pós-assinatura
- [ ] Travamento de campos após assinatura
7. Algoritmos Criptográficos
## 7.1 Algoritmos

- [ ] Algoritmos configuráveis
- [ ] SHA-384
- [ ] SHA-512
- [ ] RSA-PSS
- [ ] ECDSA
- [ ] Política de algoritmo
- [ ] Negotiation de algoritmo
- [ ] Rejeição de algoritmos inseguros
- [ ] Política mínima de hash
8. Credenciais de Assinatura
## 8.1 Credenciais

- [ ] Suporte a arquivo PFX/P12
- [ ] Suporte a PEM
- [ ] Suporte a HSM
- [ ] Suporte a PKCS#11
- [ ] Suporte a smartcard
- [ ] Suporte a cloud KMS
- [ ] Suporte a remote signing
- [ ] Callback de assinatura externa
- [ ] Assinatura desacoplada do storage da chave privada
9. Cadeia e Certificados
## 9.1 Cadeia X.509

- [ ] Trust store plugável
- [ ] Chain validator plugável
- [ ] Revocation provider plugável
- [ ] Construção de cadeia X.509
- [ ] Suporte a múltiplas trust chains
- [ ] Suporte a AIA fetching
- [ ] Cache de OCSP/CRL
10. Validação de Certificados
## 10.1 Validação Criptográfica

- [ ] Validação de certificado do signatário
- [ ] Validação de key usage
- [ ] Validação de extended key usage
- [ ] Validação de expiração
- [ ] Validação temporal por signing time
- [ ] Validação temporal por timestamp
11. Timestamp RFC 3161
## 11.1 Timestamp

- [ ] Validação RFC 3161 completa
- [ ] Validação de cadeia TSA
- [ ] Validação de política TSA
- [ ] PDF DocTimeStamp
12. PAdES-B-B

Primeiro perfil ETSI formal.

## 12.1 PAdES-B-B

- [ ] Perfis formais PAdES Baseline
- [ ] Perfil PAdES-B-B completo
- [ ] Relatório PAdES por perfil
- [ ] Validador PAdES-B-B
13. PAdES-B-T
## 13.1 PAdES-B-T

- [ ] Perfil PAdES-B-T completo
- [ ] Validador PAdES-B-T
14. DSS / VRI / LT
## 14.1 PAdES-LT

- [ ] DSS completo
- [ ] VRI completo
- [ ] Hash VRI conforme perfil aplicável
- [ ] Cadeia completa no DSS
- [ ] OCSP completo no DSS
- [ ] CRL completo no DSS
- [ ] Inclusão automática de evidências LTV
- [ ] Rebuild de DSS incremental
- [ ] Validação offline futura
- [ ] Perfil PAdES-B-LT completo
- [ ] Validador PAdES-B-LT
15. LTA

ÚLTIMA etapa.

## 15.1 PAdES-LTA

- [ ] Timestamp de documento para LTA
- [ ] Renovação de evidências criptográficas
- [ ] Estratégia de preservação criptográfica
- [ ] Suporte a archival timestamp
- [ ] Perfil PAdES-B-LTA completo
- [ ] Validador PAdES-B-LTA
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
