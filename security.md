# Security Policy

The security of our software products is essential to us and our customers. In spite of our care, procedures and best efforts it is possible that there are vulnerabilities in our software products. If you find any, please tell us as soon as possible so we can fix them.

## Reporting a Vulnerability

To report a security issue, please [email us](mailto:security@really-simple-ssl.com) with a description of the issue, the steps you took to create the issue, affected versions, and, if known, mitigations for the issue.
Please read our [Coordinated Vulnerability Disclosure Policy](https://really-simple-ssl.com/coordinated-vulnerability-disclosure-policy/) before reporting any vulnerabilities.

## Preferred languages:
en, nl

## Software Bill of Materials (SBOM)

This software includes a comprehensive Software Bill of Materials (SBOM) listing all dependencies.

**SBOM file:**
- `sbom.json.gz` - Compressed SBOM file
- Extract with: `gunzip sbom.json.gz`

**Format:** CycloneDX JSON v1.5
**Contents:** All direct and transitive dependencies from PHP (Composer) and JavaScript (npm) packages

To extract and view the compressed SBOM:
```bash
gunzip sbom.json.gz
cat sbom.json | jq .
```