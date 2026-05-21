# Checklist PAdES

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

---

# Falta Para Biblioteca PAdES Forte

## API Pública PAdES

- [ ] API pública exclusivamente PAdES
- [ ] Remover CAdES standalone da API pública
- [ ] Mover CMS para namespace interno
- [ ] Remover ICP-Brasil do core
- [ ] Remover políticas nacionais do core
- [ ] Separar engine PDF da engine criptográfica
- [ ] Abstração de signer provider
- [ ] Interface de assinatura desacoplada
- [ ] Interface de timestamp desacoplada
- [ ] Interface de trust validation desacoplada

## Perfis PAdES

- [ ] Perfis formais PAdES Baseline
- [ ] Perfil PAdES-B-B completo
- [ ] Perfil PAdES-B-T completo
- [ ] Perfil PAdES-B-LT completo
- [ ] Perfil PAdES-B-LTA completo
- [ ] Mapeamento ETSI EN 319 142
- [ ] Verificação formal de conformidade ETSI

## Estrutura PDF Robusta

- [ ] Parser PDF estrutural
- [ ] Parser incremental próprio
- [ ] Suporte a xref table
- [ ] Suporte a xref stream
- [ ] Suporte a object streams
- [ ] Suporte a PDFs linearizados
- [ ] Leitura robusta de trailer
- [ ] Resolução indireta de objetos PDF
- [ ] Merge seguro de `/Fields`
- [ ] Merge seguro de `/Annots`
- [ ] Merge seguro de `/Extensions`
- [ ] Preservação robusta de revisões
- [ ] Validação completa de xref
- [ ] Validação completa de trailer
- [ ] Proteção contra corrupção estrutural do PDF
- [ ] Suporte real a múltiplas assinaturas
- [ ] Append mode rigoroso
- [ ] Assinar PDFs já assinados
- [ ] Detecção de assinaturas existentes
- [ ] Campos de assinatura nomeáveis
- [ ] Detecção de campos de assinatura vazios
- [ ] Assinar campo existente
- [ ] Criação automática de campo de assinatura
- [ ] Suporte a PDFs sem AcroForm
- [ ] Normalização de line endings do PDF
- [ ] Preservação binária do conteúdo não assinado

## Assinaturas Avançadas PDF/PAdES

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

## Timestamp / LTV / LTA

- [ ] PDF DocTimeStamp
- [ ] Timestamp de documento para LTA
- [ ] Renovação de evidências criptográficas
- [ ] Validação RFC 3161 completa
- [ ] Validação de cadeia TSA
- [ ] Validação de política TSA
- [ ] DSS completo
- [ ] VRI completo
- [ ] Hash VRI conforme perfil aplicável
- [ ] Cadeia completa no DSS
- [ ] OCSP completo no DSS
- [ ] CRL completo no DSS
- [ ] Inclusão automática de evidências LTV
- [ ] Rebuild de DSS incremental
- [ ] Validação offline futura
- [ ] Estratégia de preservação criptográfica
- [ ] Suporte a archival timestamp

## Credenciais de Assinatura

- [ ] Credencial abstrata de assinatura
- [ ] Signer provider plugável
- [ ] Suporte a arquivo PFX/P12
- [ ] Suporte a PEM
- [ ] Suporte a HSM
- [ ] Suporte a PKCS#11
- [ ] Suporte a smartcard
- [ ] Suporte a cloud KMS
- [ ] Suporte a remote signing
- [ ] Callback de assinatura externa
- [ ] Assinatura desacoplada do storage da chave privada

## Algoritmos

- [ ] Algoritmos configuráveis
- [ ] SHA-384
- [ ] SHA-512
- [ ] RSA-PSS
- [ ] ECDSA
- [ ] Política de algoritmo
- [ ] Negotiation de algoritmo
- [ ] Rejeição de algoritmos inseguros
- [ ] Política mínima de hash

## Cadeia e Certificados

- [ ] Trust store plugável
- [ ] Chain validator plugável
- [ ] Revocation provider plugável
- [ ] Validação de certificado do signatário
- [ ] Validação de key usage
- [ ] Validação de extended key usage
- [ ] Validação de expiração
- [ ] Validação temporal por signing time
- [ ] Validação temporal por timestamp
- [ ] Construção de cadeia X.509
- [ ] Cache de OCSP/CRL
- [ ] Suporte a AIA fetching
- [ ] Suporte a múltiplas trust chains

## Validador PAdES

- [ ] Relatório PAdES por perfil
- [ ] Relatório ETSI detalhado
- [ ] Relatório de integridade PDF
- [ ] Relatório de cadeia criptográfica
- [ ] Validador PAdES-B-B
- [ ] Validador PAdES-B-T
- [ ] Validador PAdES-B-LT
- [ ] Validador PAdES-B-LTA
- [ ] Detecção de alteração pós-assinatura
- [ ] Verificação de cobertura ByteRange
- [ ] Verificação de integridade incremental

## Interoperabilidade

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

## Performance e Segurança

- [ ] Streaming de PDFs grandes
- [ ] Assinatura sem carregar PDF inteiro em memória
- [ ] Proteção contra malformed PDFs
- [ ] Proteção contra object injection
- [ ] Proteção contra xref corruption
- [ ] Limites de memória configuráveis
- [ ] Limites de tamanho configuráveis
- [ ] Timeout configurável para TSA/OCSP
- [ ] Hardenização ASN.1 parser

## Documentação

- [ ] Documentação de API PAdES
- [ ] Documentação arquitetural
- [ ] Guia de interoperabilidade
- [ ] Guia de múltiplas assinaturas
- [ ] Guia de timestamp
- [ ] Guia de LTV/LTA
- [ ] Guia de signer providers
- [ ] Matriz de conformidade ETSI EN 319 142
- [ ] Matriz de conformidade ISO 32000

---

# Remover do Escopo

- [x] CAdES standalone
- [x] API para gerar `.p7s`
- [x] API para gerar `.p7m`
- [x] Assinatura detached fora do PDF como produto final
- [x] ICP-Brasil no core
- [x] e-CPF no core
- [x] e-CNPJ no core
- [x] AC Raiz Brasileira hardcoded
- [x] Políticas nacionais hardcoded
- [x] API HTTP/SaaS
- [x] Assinatura XML
- [x] XAdES
- [x] Assinatura genérica de binários
- [x] Engine genérica PKCS#7
- [x] Ferramenta de certificação nacional
- [x] Regras jurídicas brasileiras no core
