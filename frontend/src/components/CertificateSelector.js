import React, { useState, useEffect } from 'react';
import { toast } from 'react-toastify';

const CertificateSelector = ({ onCertificateSelect, selectedCertificate }) => {
  const [certificates, setCertificates] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Função para listar certificados instalados no Windows
  const loadInstalledCertificates = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // Verifica se estamos em um ambiente que suporta a API de certificados
      if (!window.crypto || !window.crypto.subtle) {
        throw new Error('API de criptografia não suportada neste navegador');
      }

      // Para Windows, tentamos acessar o Certificate Store através da API Web Crypto
      // Nota: Esta é uma implementação limitada, pois navegadores têm restrições de segurança
      const certs = await getCertificatesFromStore();
      setCertificates(certs);
      
    } catch (err) {
      console.error('Erro ao carregar certificados:', err);
      setError('Não foi possível carregar os certificados instalados. Use a opção de arquivo.');
      toast.warning('Certificados instalados não disponíveis. Use upload de arquivo.');
    } finally {
      setLoading(false);
    }
  };

  // Função auxiliar para tentar obter certificados (implementação limitada)
  const getCertificatesFromStore = async () => {
    // Esta é uma implementação simplificada
    // Em um ambiente real, seria necessário um componente nativo ou extensão
    return [];
  };

  useEffect(() => {
    loadInstalledCertificates();
  }, []);

  const handleCertificateSelect = (certificate) => {
    onCertificateSelect(certificate);
  };

  if (loading) {
    return (
      <div style={{ padding: '16px', textAlign: 'center' }}>
        <div style={{ marginBottom: '8px' }}>Carregando certificados instalados...</div>
        <div style={{ fontSize: '12px', color: '#6b7280' }}>Aguarde...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: '16px', textAlign: 'center', color: '#ef4444' }}>
        <div style={{ marginBottom: '8px' }}>⚠️ {error}</div>
        <button
          onClick={loadInstalledCertificates}
          style={{
            padding: '4px 8px',
            fontSize: '12px',
            border: '1px solid #d1d5db',
            borderRadius: '4px',
            background: '#f9fafb',
            cursor: 'pointer'
          }}
        >
          Tentar novamente
        </button>
      </div>
    );
  }

  if (certificates.length === 0) {
    return (
      <div style={{ padding: '16px', textAlign: 'center', color: '#6b7280' }}>
        <div style={{ marginBottom: '8px' }}>Nenhum certificado encontrado</div>
        <div style={{ fontSize: '12px' }}>Use a opção de upload de arquivo</div>
      </div>
    );
  }

  return (
    <div style={{ maxHeight: '200px', overflowY: 'auto', border: '1px solid #d1d5db', borderRadius: '6px' }}>
      {certificates.map((cert, index) => (
        <div
          key={index}
          onClick={() => handleCertificateSelect(cert)}
          style={{
            padding: '12px',
            borderBottom: index < certificates.length - 1 ? '1px solid #e5e7eb' : 'none',
            cursor: 'pointer',
            backgroundColor: selectedCertificate?.thumbprint === cert.thumbprint ? '#eff6ff' : 'transparent',
            ':hover': {
              backgroundColor: '#f9fafb'
            }
          }}
          onMouseEnter={(e) => {
            if (selectedCertificate?.thumbprint !== cert.thumbprint) {
              e.target.style.backgroundColor = '#f9fafb';
            }
          }}
          onMouseLeave={(e) => {
            if (selectedCertificate?.thumbprint !== cert.thumbprint) {
              e.target.style.backgroundColor = 'transparent';
            }
          }}
        >
          <div style={{ fontWeight: '500', fontSize: '14px', marginBottom: '4px' }}>
            {cert.subject || 'Certificado'}
          </div>
          <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '2px' }}>
            Emissor: {cert.issuer || 'N/A'}
          </div>
          <div style={{ fontSize: '12px', color: '#6b7280' }}>
            Válido até: {cert.validTo ? new Date(cert.validTo).toLocaleDateString('pt-BR') : 'N/A'}
          </div>
        </div>
      ))}
    </div>
  );
};

export default CertificateSelector;