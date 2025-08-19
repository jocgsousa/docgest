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
  background-color: ${props => props.theme.colors.error + '20'};
  color: ${props => props.theme.colors.error};
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
`;

const SuccessMessage = styled.div`
  background-color: ${props => props.theme.colors.success + '20'};
  color: ${props => props.theme.colors.success};
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
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
    const tipo = searchParams.get('tipo');
    const teste = searchParams.get('teste');
    
    if (tipo === 'empresa' || tipo === 'assinante') {
      setAccountType(tipo);
    }
    
    if (teste === 'true') {
      setIsTest(true);
    }
  }, [searchParams]);

  const handleTypeChange = (type) => {
    setAccountType(type);
    setError('');
    setSuccess('');
  };

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

      await api.post('/auth/register', payload);
      
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

        <TypeSelector>
          <TypeOption 
            type="button"
            $selected={accountType === 'empresa'}
            onClick={() => handleTypeChange('empresa')}
          >
            <TypeIcon $selected={accountType === 'empresa'}>
              <Building2 size={24} />
            </TypeIcon>
            <TypeTitle $selected={accountType === 'empresa'}>Empresa</TypeTitle>
            <TypeDescription>
              Para empresas que desejam usar a plataforma para gestão de documentos
            </TypeDescription>
          </TypeOption>
          
          <TypeOption 
            type="button"
            $selected={accountType === 'assinante'}
            onClick={() => handleTypeChange('assinante')}
          >
            <TypeIcon $selected={accountType === 'assinante'}>
              <User size={24} />
            </TypeIcon>
            <TypeTitle $selected={accountType === 'assinante'}>Assinante</TypeTitle>
            <TypeDescription>
              Para pessoas que irão receber e assinar documentos
            </TypeDescription>
          </TypeOption>
        </TypeSelector>

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