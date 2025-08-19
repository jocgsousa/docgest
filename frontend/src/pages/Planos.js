import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import { useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import LandingHeader from '../components/layout/LandingHeader';
import { publicApi } from '../services/api';
import { Check, Star, Zap, Building2 } from 'lucide-react';

const Container = styled.div`
  min-height: 100vh;
  background-color: ${props => props.theme.colors.white};
`;

const HeroSection = styled.section`
  padding: 4rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, ${props => props.theme.colors.gray[50]} 0%, ${props => props.theme.colors.white} 100%);
`;

const HeroContent = styled.div`
  max-width: 4xl;
  margin: 0 auto;
`;

const HeroTitle = styled.h1`
  font-size: 3rem;
  font-weight: 800;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1.5rem;
  line-height: 1.1;
  
  @media (max-width: 768px) {
    font-size: 2.25rem;
  }
`;

const HeroSubtitle = styled.p`
  font-size: 1.25rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2.5rem;
  max-width: 3xl;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.6;
`;

const PlansSection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.white};
`;

const SectionContainer = styled.div`
  max-width: 6xl;
  margin: 0 auto;
`;

const PlansGrid = styled.div`
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 2rem;
  margin-bottom: 4rem;
`;

const LoadingContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  font-size: 1.1rem;
  color: #666;
`;

const ErrorContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  font-size: 1.1rem;
  color: #e74c3c;
  text-align: center;
`;

const EmptyContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
  font-size: 1.1rem;
  color: #666;
  text-align: center;
`;

const PlanCard = styled.div`
  background-color: ${props => props.theme.colors.white};
  border: 2px solid ${props => props.$popular ? props.theme.colors.primary : props.theme.colors.gray[200]};
  border-radius: 1rem;
  padding: 2rem;
  position: relative;
  transition: all 0.3s ease-in-out;
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }
`;

const PopularBadge = styled.div`
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background-color: ${props => props.theme.colors.primary};
  color: ${props => props.theme.colors.white};
  padding: 0.5rem 1.5rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
`;

const PlanIcon = styled.div`
  width: 3rem;
  height: 3rem;
  background-color: ${props => props.color + '20'};
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: ${props => props.color};
`;

const PlanName = styled.h3`
  font-size: 1.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 0.5rem;
`;

const PlanDescription = styled.p`
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 1.5rem;
  line-height: 1.5;
`;

const PlanPrice = styled.div`
  margin-bottom: 2rem;
`;

const Price = styled.div`
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
`;

const PriceAmount = styled.span`
  font-size: 3rem;
  font-weight: 800;
  color: ${props => props.theme.colors.gray[900]};
`;

const PriceCurrency = styled.span`
  font-size: 1.25rem;
  font-weight: 600;
  color: ${props => props.theme.colors.gray[600]};
`;

const PricePeriod = styled.span`
  font-size: 1rem;
  color: ${props => props.theme.colors.gray[500]};
`;

const PriceNote = styled.p`
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[500]};
`;

const FeaturesList = styled.ul`
  list-style: none;
  padding: 0;
  margin: 0 0 2rem 0;
`;

const FeatureItem = styled.li`
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  color: ${props => props.theme.colors.gray[700]};
`;

const FeatureIcon = styled.div`
  color: ${props => props.theme.colors.success};
  margin-top: 0.125rem;
`;

const CTASection = styled.section`
  padding: 4rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[50]};
  text-align: center;
`;

const CTATitle = styled.h2`
  font-size: 2.5rem;
  font-weight: 700;
  color: ${props => props.theme.colors.gray[900]};
  margin-bottom: 1rem;
  
  @media (max-width: 768px) {
    font-size: 2rem;
  }
`;

const CTASubtitle = styled.p`
  font-size: 1.125rem;
  color: ${props => props.theme.colors.gray[600]};
  margin-bottom: 2.5rem;
  max-width: 2xl;
  margin-left: auto;
  margin-right: auto;
`;

const CTAButtons = styled.div`
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
`;

const Footer = styled.footer`
  padding: 2rem 1.5rem;
  background-color: ${props => props.theme.colors.gray[900]};
  text-align: center;
`;

const FooterText = styled.p`
  color: ${props => props.theme.colors.gray[400]};
  font-size: 0.875rem;
`;

function Planos() {
  const navigate = useNavigate();
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchPlans();
  }, []);

  const fetchPlans = async () => {
    try {
      setLoading(true);
      const response = await publicApi.getPublicPlans();
      setPlans(response.data.data || []);
    } catch (err) {
      console.error('Erro ao buscar planos:', err);
      setError('Erro ao carregar planos. Tente novamente mais tarde.');
    } finally {
      setLoading(false);
    }
  };

  const handleTestClick = () => {
    navigate('/cadastro?tipo=empresa&teste=true');
  };

  const handleSignupClick = (planType) => {
    navigate(`/cadastro?tipo=empresa&plano=${planType}`);
  };

  const handleContactClick = () => {
    navigate('/contato');
  };



  return (
    <Container>
      <LandingHeader />
      
      <HeroSection>
        <HeroContent>
          <HeroTitle>
            Escolha o Plano Ideal
          </HeroTitle>
          <HeroSubtitle>
            Oferecemos planos flexíveis para empresas de todos os tamanhos. 
            Comece com nosso teste gratuito de 30 dias e escolha o plano que melhor se adapta às suas necessidades.
          </HeroSubtitle>
        </HeroContent>
      </HeroSection>

      <PlansSection>
        <SectionContainer>
          {loading && (
            <LoadingContainer>
              Carregando planos...
            </LoadingContainer>
          )}
          
          {error && (
            <ErrorContainer>
              {error}
            </ErrorContainer>
          )}
          
          {!loading && !error && plans.length === 0 && (
            <EmptyContainer>
              Nenhum plano disponível no momento.
            </EmptyContainer>
          )}
          
          {!loading && !error && plans.length > 0 && (
            <PlansGrid>
              {plans.map((plan, index) => (
                <PlanCard key={plan.id} $popular={plan.popular}>
                  {plan.popular && (
                    <PopularBadge>
                      <Star size={16} />
                      Mais Popular
                    </PopularBadge>
                  )}
                  
                  <PlanIcon color={plan.color}>
                    {plan.icon}
                  </PlanIcon>
                  
                  <PlanName>{plan.nome}</PlanName>
                  <PlanDescription>{plan.descricao}</PlanDescription>
                  
                  <PlanPrice>
                    <Price>
                      <PriceAmount>{plan.preco ? `R$ ${plan.preco}` : 'Consulte'}</PriceAmount>
                      {plan.preco && <PricePeriod>/mês</PricePeriod>}
                    </Price>
                    {plan.limite_usuarios && (
                      <PriceNote>
                        {plan.limite_usuarios === -1 
                          ? 'Usuários ilimitados' 
                          : `Até ${plan.limite_usuarios} usuários`
                        }
                      </PriceNote>
                    )}
                  </PlanPrice>
                  
                  <FeaturesList>
                    {plan.limite_documentos && (
                      <FeatureItem>
                        <FeatureIcon>
                          <Check size={16} />
                        </FeatureIcon>
                        {plan.limite_documentos === -1 
                          ? 'Documentos ilimitados' 
                          : `${plan.limite_documentos} documentos/mês`
                        }
                      </FeatureItem>
                    )}
                    {plan.limite_assinaturas && (
                      <FeatureItem>
                        <FeatureIcon>
                          <Check size={16} />
                        </FeatureIcon>
                        {plan.limite_assinaturas === -1 
                          ? 'Assinaturas ilimitadas' 
                          : `${plan.limite_assinaturas} assinaturas/mês`
                        }
                      </FeatureItem>
                    )}
                    {plan.limite_filiais && (
                      <FeatureItem>
                        <FeatureIcon>
                          <Check size={16} />
                        </FeatureIcon>
                        {plan.limite_filiais === 999999 
                          ? 'Filiais ilimitadas' 
                          : `${plan.limite_filiais} ${plan.limite_filiais === 1 ? 'filial' : 'filiais'}`
                        }
                      </FeatureItem>
                    )}
                    {plan.recursos && JSON.parse(plan.recursos).map((recurso, idx) => (
                      <FeatureItem key={idx}>
                        <FeatureIcon>
                          <Check size={16} />
                        </FeatureIcon>
                        {recurso}
                      </FeatureItem>
                    ))}
                  </FeaturesList>
                  
                  <Button 
                    $variant={plan.popular ? 'primary' : 'outline'}
                    size="lg"
                    $fullWidth
                    onClick={() => {
                      if (!plan.preco) {
                        handleContactClick();
                      } else {
                        handleSignupClick(plan.nome.toLowerCase());
                      }
                    }}
                  >
                    {plan.preco ? 'Começar Agora' : 'Falar com Vendas'}
                  </Button>
                </PlanCard>
              ))}
            </PlansGrid>
          )}
        </SectionContainer>
      </PlansSection>

      <CTASection>
        <SectionContainer>
          <CTATitle>Ainda tem dúvidas?</CTATitle>
          <CTASubtitle>
            Experimente nossa plataforma gratuitamente por 5 dias. 
            Sem compromisso, sem cartão de crédito. Cancele quando quiser.
          </CTASubtitle>
          <CTAButtons>
            <Button 
              $variant="primary" 
              size="lg"
              onClick={handleTestClick}
            >
              Começar Teste Gratuito
            </Button>
            <Button 
              $variant="outline" 
              size="lg"
              onClick={handleContactClick}
            >
              Falar com Especialista
            </Button>
          </CTAButtons>
        </SectionContainer>
      </CTASection>

      <Footer>
        <FooterText>
          © 2024 DocGest. Todos os direitos reservados.
        </FooterText>
      </Footer>
    </Container>
  );
}

export default Planos;