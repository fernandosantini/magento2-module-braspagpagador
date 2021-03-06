<?php

/**
 * @author      Webjump Core Team <dev@webjump.com.br>
 * @copyright   2017 Webjump (http://www.webjump.com.br)
 * @license     http://www.webjump.com.br  Copyright
 *
 * @link        http://www.webjump.com.br
 */

namespace Webjump\BraspagPagador\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Webjump\BraspagPagador\Api\CardTokenManagerInterface;
use Webjump\BraspagPagador\Api\CardTokenRepositoryInterface;

class CardTokenManager implements CardTokenManagerInterface
{
    /**
     * @var
     */
    protected $cardTokenRepository;

    /**
     * @var
     */
    protected $eventManager;

    /**
     * @var
     */
    protected $searchCriteriaBuilder;


    /**
     * CardTokenHandler constructor.
     *
     * @param CardTokenRepositoryInterface $cardTokenRepository
     * @param ManagerInterface             $eventManager
     * @param SearchCriteriaBuilder        $searchCriteriaBuilder
     */
    public function __construct(
        CardTokenRepositoryInterface $cardTokenRepository,
        ManagerInterface $eventManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->setCardTokenRepository($cardTokenRepository);
        $this->setEventManager($eventManager);
        $this->setSearchCriteriaBuilder($searchCriteriaBuilder);
    }

    public function registerCardToken($customerId, $paymentMethod, $response)
    {
        if ($cardToken = $this->getCardTokenRepository()->get($response->getPaymentCardToken())) {
            return $cardToken;
        }
        $searchCriteriaBuilder = $this->getSearchCriteriaBuilder();
        $searchCriteriaBuilder->addFilter('method', $paymentMethod);
        $searchCriteriaBuilder->addFilter('customer_id', $customerId);
        $searchCriteria = $searchCriteriaBuilder->create();

        $searchResult = $this->getCardTokenRepository()->getList($searchCriteria);

        foreach ($searchResult->getItems() as $item) {
            $this->deleteCardToken($item);
        }

        $data = new DataObject([
            'alias' => $response->getPaymentCardNumberEncrypted(),
            'token' => $response->getPaymentCardToken(),
            'provider' => $response->getPaymentCardProvider(),
            'brand' => $response->getPaymentCardBrand(),
            'method' => $paymentMethod,
        ]);

        $cardToken = $this->getCardTokenRepository()->create($data->toArray());

        $this->getCardTokenRepository()->save($cardToken);

        return $cardToken;
    }

    protected function deleteCardToken($cardToken)
    {
        $this->getCardTokenRepository()->delete($cardToken);
    }

    /**
     * @return mixed
     */
    protected function getSearchCriteriaBuilder()
    {
        return $this->searchCriteriaBuilder;
    }

    /**
     * @param mixed $searchCriteriaBuilder
     */
    protected function setSearchCriteriaBuilder($searchCriteriaBuilder)
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return mixed
     */
    protected function getCardTokenRepository()
    {
        return $this->CardTokenRepository;
    }

    /**
     * @param CardTokenRepositoryInterface $cardTokenRepository
     *
     * @return $this
     */
    protected function setCardTokenRepository(CardTokenRepositoryInterface $cardTokenRepository)
    {
        $this->CardTokenRepository = $cardTokenRepository;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * @param $eventManager
     *
     * @return $this
     */
    protected function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;

        return $this;
    }
}
