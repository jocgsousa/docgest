import React, { useState } from 'react';
import styled from 'styled-components';
import Button from './Button';
import Input from './Input';
import Modal from './Modal';
import api from '../services/api';

const FormGrid = styled.div`
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-bottom: 16px;
`;

const LinkContainer = styled.div`
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
`;

const LinkText = styled.div`
  font-family: monospace;
  font-size: 14px;
  word-break: break-all;
  background-color: white;
  padding: 12px;
  border-radius: 4px;
  border: 1px solid #d1d5db;
  margin-bottom: 12px;
`;

const CopyButton = styled(Button)`
  width: 100%;
`;

const SuccessMessage = styled.div`
  background-color: #dcfce7;
  color: #166534;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 14px;
`;

const ErrorMessage = styled.div`
  background-color: #fee2e2;
  color: #991b1b;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-size: 14px;
`;

const RegistrationLinkGenerator = ({ isOpen, onClose, companies, userCompanyId }) => {
  const [formData, setFormData] = useState({
    empresa_id: userCompanyId || '',
    tipo_usuario: 'assinante',
    email_destinatario: ''
  });
  const [generatedLink, setGeneratedLink] = useState('');
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [copied, setCopied] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');
    setGeneratedLink('');

    try {
      const response = await api.post('/users/generate-registration-link', formData);
      
      if (response.data.success) {
        setGeneratedLink(response.data.data.url);
        setSuccess('Link de cadastro gerado com sucesso! Válido por 30 minutos.');
      } else {
        setError(response.data.message || 'Erro ao gerar link de cadastro');
      }
    } catch (error) {
      console.error('Erro ao gerar link:', error);
      setError(error.response?.data?.message || 'Erro ao gerar link de cadastro');
    } finally {
      setLoading(false);
    }
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(generatedLink);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (error) {
      console.error('Erro ao copiar link:', error);
      // Fallback para navegadores mais antigos
      const textArea = document.createElement('textarea');
      textArea.value = generatedLink;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const handleClose = () => {
    setFormData({
      empresa_id: userCompanyId || '',
      tipo_usuario: 'assinante',
      email_destinatario: ''
    });
    setGeneratedLink('');
    setSuccess('');
    setError('');
    setCopied(false);
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Gerar Link de Cadastro"
    >
      <form onSubmit={handleSubmit}>
        {error && <ErrorMessage>{error}</ErrorMessage>}
        {success && <SuccessMessage>{success}</SuccessMessage>}
        
        <FormGrid>
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500' }}>
              Empresa
            </label>
            <select
              value={formData.empresa_id}
              onChange={(e) => setFormData(prev => ({ ...prev, empresa_id: e.target.value }))}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
              required
            >
              <option value="">Selecione uma empresa</option>
              {companies.map(company => (
                <option key={company.id} value={company.id}>
                  {company.nome}
                </option>
              ))}
            </select>
          </div>

          <Input
            label="Email do Destinatário (opcional)"
            type="email"
            value={formData.email_destinatario}
            onChange={(e) => setFormData(prev => ({ ...prev, email_destinatario: e.target.value }))}
            placeholder="email@exemplo.com"
          />
        </FormGrid>

        {generatedLink && (
          <LinkContainer>
            <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', fontWeight: '500' }}>
              Link de Cadastro Gerado:
            </label>
            <LinkText>{generatedLink}</LinkText>
            <CopyButton
              type="button"
              $variant={copied ? 'success' : 'primary'}
              onClick={handleCopyLink}
            >
              {copied ? '✓ Copiado!' : 'Copiar Link'}
            </CopyButton>
          </LinkContainer>
        )}

        <div style={{ display: 'flex', gap: '12px', justifyContent: 'flex-end', marginTop: '24px' }}>
          <Button
            type="button"
            $variant="outline"
            onClick={handleClose}
          >
            Fechar
          </Button>
          <Button
            type="submit"
            disabled={loading || !formData.empresa_id}
          >
            {loading ? 'Gerando...' : 'Gerar Link'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

export default RegistrationLinkGenerator;