import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import { Building2, User, ArrowLeft } from 'lucide-react';
import api from '../services/api';

const Container = styled.div`
  min-height: 100vh;
  background: linear-gradient(135deg, ${props => props.theme.colors.gray[50]} 0%, ${props => props.theme.colors.white} 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
`;

const SignupCard = styled(Card)`
  width: 100%;
  max-width: 500px;
  padding: 2rem;
`;

const Header = styled.div`
  text-align: center;
  margin-bottom: 2rem;
`;

const BackButton = styled(Link)`
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: ${props => props.theme.colors.gray[600]};
  text-decoration: none;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  
  &:hover {
    color: ${props => props.theme.colors.gray[900]};
  }
`;

const Title = styled.h1`
  font-size: 1.875rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.5rem;
`;

const Subtitle = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  font-size: 1rem;
`;

const TypeSelector = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 2rem;
`;

const TypeOption = styled.button`
  padding: 1.5rem;
  border: 2px solid ${props => props.$selected ? props.theme.colors.primary : props.theme.colors.gray[200]};
  border-radius: 0.75rem;
  background-color: ${props => props.$selected ? props.theme.colors.primary + '10' : props.theme.colors.white};
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  text-align: center;
  
  &:hover {
    border-color: ${props => props.theme.colors.primary};
  }
`;

const TypeIcon = styled.div`
  display: flex;
  justify-content: center;
  margin-bottom: 0.75rem;
  color: ${props => props.$selected ? props.theme.colors.primary : props.theme.colors.gray[500]};
`;

const TypeTitle = styled.h3`
  font-size: 1rem;
  font-weight: 600;
  color: ${props => props.$selected ? props.theme.colors.primary : props.theme.colors.gray[900]};
  margin-bottom: 0.5rem;
`;

const TypeDescription = styled.p`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[600]};
  line-height: 1.4;
`;

const Form = styled.form`
  display: flex;
  flex-direction: column;
  gap: 1rem;
`;

const FormRow = styled.div`
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  
  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }
`;

const ErrorMessage = styled.div`
  background-color: ${props => props.theme.colors.error + '15'};
  color: ${props => props.theme.colors.error};
  border: 2px solid ${props => props.theme.colors.error};
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px ${props => props.theme.colors.error + '25'};
  animation: slideIn 0.3s ease-out;
  position: relative;
  
  &::before {
    content: '⚠️';
    font-size: 1.2rem;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;

const SuccessMessage = styled.div`
  background-color: ${props => props.theme.colors.success + '15'};
  color: ${props => props.theme.colors.success};
  border: 2px solid ${props => props.theme.colors.success};
  padding: 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px ${props => props.theme.colors.success + '25'};
  animation: slideIn 0.3s ease-out;
  position: relative;
  
  &::before {
    content: '✅';
    font-size: 1.2rem;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;

const LoginLink = styled.div`
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid ${props => props.theme.colors.gray[200]};
  
  p {
    color: ${props => props.theme.colors.gray[600]};
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }
  
  a {
    color: ${props => props.theme.colors.primary};
    text-decoration: none;
    font-weight: 500;
    
    &:hover {
      text-decoration: underline;
    }
  }
`;

const TestBadge = styled.div`
  background-color: ${props => props.theme.colors.warning + '20'};
  color: ${props => props.theme.colors.warning};
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  text-align: center;
  margin-bottom: 1rem;
`;

function Cadastro() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [accountType, setAccountType] = useState('empresa');
  const [isTest, setIsTest] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [tokenData, setTokenData] = useState(null);
  const [validatingToken, setValidatingToken] = useState(false);
  const [formData, setFormData] = useState({
    // Campos comuns
    nome: '',
    email: '',
    senha: '',
    confirmarSenha: '',
    telefone: '',
    
    // Campos específicos da empresa
    nomeEmpresa: '',
    cnpj: '',
    endereco: '',
    cidade: '',
    estado: '',
    cep: '',
    
    // Campos específicos do assinante
    cpf: '',
    codigoEmpresa: ''
  });

  useEffect(() => {
    const teste = searchParams.get('teste');
    const token = searchParams.get('token');
    
    // Tipo padrão é sempre 'empresa', exceto quando token de assinante é validado
    setAccountType('empresa');
    
    if (teste === 'true') {
      setIsTest(true);
    }
    
    // Validar token de autorização se presente
    if (token) {
      validateRegistrationToken(token);
    }
  }, [searchParams]);
  
  const validateRegistrationToken = async (token) => {
    setValidatingToken(true);
    setError('');
    
    try {
      const response = await api.post('/users/validate-registration-token', { token });
      
      if (response.data.success) {
        const tokenData = response.data.data.token_data;
        setTokenData(tokenData);
        
        // Preencher automaticamente os dados
        setAccountType('assinante'); // Forçar tipo assinante
        setFormData(prev => ({
          ...prev,
          codigoEmpresa: tokenData.empresa_codigo || '',
          email: tokenData.email_destinatario || prev.email
        }));
      } else {
        setError('Link de cadastro inválido ou expirado.');
      }
    } catch (error) {
      console.error('Erro ao validar token:', error);
      setError('Link de cadastro inválido ou expirado.');
    } finally {
      setValidatingToken(false);
    }
  };

  // Função handleTypeChange removida - tipo é definido automaticamente

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const validateForm = () => {
    if (!formData.nome || !formData.email || !formData.senha || !formData.confirmarSenha) {
      setError('Todos os campos obrigatórios devem ser preenchidos.');
      return false;
    }

    if (formData.senha !== formData.confirmarSenha) {
      setError('As senhas não coincidem.');
      return false;
    }

    if (formData.senha.length < 6) {
      setError('A senha deve ter pelo menos 6 caracteres.');
      return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      setError('Por favor, insira um e-mail válido.');
      return false;
    }

    if (accountType === 'empresa') {
      if (!formData.nomeEmpresa || !formData.cnpj) {
        setError('Nome da empresa e CNPJ são obrigatórios.');
        return false;
      }
    }

    if (accountType === 'assinante') {
      if (!formData.cpf || !formData.codigoEmpresa) {
        setError('CPF e código da empresa são obrigatórios.');
        return false;
      }
    }

    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      const payload = {
        nome: formData.nome,
        email: formData.email,
        senha: formData.senha,
        telefone: formData.telefone,
        tipo: accountType,
        teste: isTest
      };

      // Incluir token se presente
      const token = searchParams.get('token');
      if (token) {
        payload.token = token;
      }

      if (accountType === 'empresa') {
        payload.nomeEmpresa = formData.nomeEmpresa;
        payload.cnpj = formData.cnpj;
        payload.endereco = formData.endereco;
        payload.cidade = formData.cidade;
        payload.estado = formData.estado;
        payload.cep = formData.cep;
      } else {
        payload.cpf = formData.cpf;
        payload.codigoEmpresa = formData.codigoEmpresa;
      }

      // Usar endpoint específico baseado no tipo de conta
      const endpoint = accountType === 'assinante' ? '/auth/register-external' : '/auth/register';
      const response = await api.post(endpoint, payload);
      
      // Marcar token como usado se presente
      if (token && response.data.success) {
        try {
          await api.post('/users/mark-token-used', { 
            token, 
            usuario_criado_id: response.data.data?.id 
          });
        } catch (tokenError) {
          console.error('Erro ao marcar token como usado:', tokenError);
          // Não interromper o fluxo se falhar ao marcar o token
        }
      }
      
      setSuccess('Cadastro realizado com sucesso! Você será redirecionado para o login.');
      
      setTimeout(() => {
        navigate('/login');
      }, 2000);
      
    } catch (err) {
      setError(err.response?.data?.message || 'Erro ao realizar cadastro. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Container>
      <SignupCard>
        <BackButton to="/">
          <ArrowLeft size={16} />
          Voltar ao início
        </BackButton>
        
        <Header>
          <Title>Criar Conta</Title>
          <Subtitle>
            {isTest ? 'Cadastre-se para começar seu teste gratuito' : 'Junte-se à nossa plataforma'}
          </Subtitle>
        </Header>

        {isTest && (
          <TestBadge>
            🎉 Teste Gratuito - Experimente todas as funcionalidades por 30 dias!
          </TestBadge>
        )}

        {validatingToken && (
          <div style={{ textAlign: 'center', padding: '20px' }}>
            <p>Validando link de cadastro...</p>
          </div>
        )}
        
        {tokenData && (
          <div style={{ 
            backgroundColor: '#dcfce7', 
            color: '#166534', 
            padding: '12px', 
            borderRadius: '6px', 
            marginBottom: '16px',
            textAlign: 'center'
          }}>
            ✅ Link válido! Cadastro para empresa: <strong>{tokenData.empresa_nome}</strong>
          </div>
        )}
        
        {/* Seletor de tipo removido - padrão é 'empresa', exceto quando token de assinante é validado */}
        {tokenData && (
          <div style={{ 
            backgroundColor: '#f3f4f6', 
            color: '#374151', 
            padding: '12px', 
            borderRadius: '6px', 
            marginBottom: '16px',
            textAlign: 'center',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '8px'
          }}>
            <User size={20} />
            <span>Cadastro de <strong>Assinante</strong> para empresa: <strong>{tokenData.empresa_nome}</strong></span>
          </div>
        )}
        
        {!tokenData && (
          <div style={{ 
            backgroundColor: '#f3f4f6', 
            color: '#374151', 
            padding: '12px', 
            borderRadius: '6px', 
            marginBottom: '16px',
            textAlign: 'center',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '8px'
          }}>
            <Building2 size={20} />
            <span>Cadastro de <strong>Empresa</strong></span>
          </div>
        )}

        {error && <ErrorMessage>{error}</ErrorMessage>}
        {success && <SuccessMessage>{success}</SuccessMessage>}

        <Form onSubmit={handleSubmit}>
          <FormRow>
            <Input
              label="Nome Completo *"
              name="nome"
              type="text"
              value={formData.nome}
              onChange={handleChange}
              placeholder="Seu nome completo"
              required
            />
            <Input
              label="E-mail *"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
              placeholder="seu@email.com"
              required
            />
          </FormRow>

          <FormRow>
            <Input
              label="Senha *"
              name="senha"
              type="password"
              value={formData.senha}
              onChange={handleChange}
              placeholder="Mínimo 6 caracteres"
              required
            />
            <Input
              label="Confirmar Senha *"
              name="confirmarSenha"
              type="password"
              value={formData.confirmarSenha}
              onChange={handleChange}
              placeholder="Confirme sua senha"
              required
            />
          </FormRow>

          <Input
            label="Telefone"
            name="telefone"
            type="tel"
            value={formData.telefone}
            onChange={handleChange}
            placeholder="(11) 99999-9999"
          />

          {accountType === 'empresa' && (
            <>
              <FormRow>
                <Input
                  label="Nome da Empresa *"
                  name="nomeEmpresa"
                  type="text"
                  value={formData.nomeEmpresa}
                  onChange={handleChange}
                  placeholder="Nome da sua empresa"
                  required
                />
                <Input
                  label="CNPJ *"
                  name="cnpj"
                  type="text"
                  value={formData.cnpj}
                  onChange={handleChange}
                  placeholder="00.000.000/0000-00"
                  required
                />
              </FormRow>

              <Input
                label="Endereço"
                name="endereco"
                type="text"
                value={formData.endereco}
                onChange={handleChange}
                placeholder="Endereço completo"
              />

              <FormRow>
                <Input
                  label="Cidade"
                  name="cidade"
                  type="text"
                  value={formData.cidade}
                  onChange={handleChange}
                  placeholder="Cidade"
                />
                <Input
                  label="Estado"
                  name="estado"
                  type="text"
                  value={formData.estado}
                  onChange={handleChange}
                  placeholder="UF"
                />
              </FormRow>

              <Input
                label="CEP"
                name="cep"
                type="text"
                value={formData.cep}
                onChange={handleChange}
                placeholder="00000-000"
              />
            </>
          )}

          {accountType === 'assinante' && (
            <>
              <FormRow>
                <Input
                  label="CPF *"
                  name="cpf"
                  type="text"
                  value={formData.cpf}
                  onChange={handleChange}
                  placeholder="000.000.000-00"
                  required
                />
                <Input
                  label="Código da Empresa *"
                  name="codigoEmpresa"
                  type="text"
                  value={formData.codigoEmpresa}
                  onChange={handleChange}
                  placeholder="Código fornecido pela empresa"
                  required
                />
              </FormRow>
            </>
          )}

          <Button 
            type="submit" 
            variant="primary" 
            size="lg" 
            $fullWidth
            disabled={loading}
          >
            {loading ? 'Criando conta...' : 'Criar Conta'}
          </Button>
        </Form>

        <LoginLink>
          <p>Já tem uma conta?</p>
          <Link to="/login">Fazer login</Link>
        </LoginLink>
      </SignupCard>
    </Container>
  );
}

export default Cadastro;