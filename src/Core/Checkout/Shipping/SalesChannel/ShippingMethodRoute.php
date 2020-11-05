<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Shipping\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\Entity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ShippingMethodRoute extends AbstractShippingMethodRoute
{
    /**
     * @var SalesChannelRepositoryInterface
     */
    private $shippingMethodRepository;

    public function __construct(
        SalesChannelRepositoryInterface $shippingMethodRepository
    ) {
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function getDecorated(): AbstractShippingMethodRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("6.2.0.0")
     * @Entity("shipping_method")
     * @OA\Get(
     *      path="/shipping-method",
     *      summary="Loads all available shipping methods",
     *      operationId="readShippingMethod",
     *      tags={"Store API", "Shipping Method"},
     *      @OA\Parameter(name="Api-Basic-Parameters"),
     *      @OA\Parameter(
     *          parameter="onlyAvailable",
     *          name="onlyAvailable",
     *          in="query",
     *          description="Encoded SwagQL in JSON",
     *          @OA\Schema(type="int")
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total amount"
     *              ),
     *              @OA\Property(
     *                  property="aggregations",
     *                  type="object",
     *                  description="aggregation result"
     *              ),
     *              @OA\Property(
     *                  property="elements",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/shipping_method_flat")
     *              )
     *          )
     *     )
     * )
     * @Route("/store-api/v{version}/shipping-method", name="store-api.shipping.method", methods={"GET", "POST"})
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ShippingMethodRouteResponse
    {
        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addAssociation('media');

        $shippingMethods = $this->shippingMethodRepository->search($criteria, $context);

        if ($request->query->getBoolean('onlyAvailable', false)) {
            /** @var ShippingMethodCollection $collection */
            $collection = $shippingMethods->getEntities();
            $shippingMethods->assign(['entities' => $collection->filterByActiveRules($context)]);
        }

        return new ShippingMethodRouteResponse($shippingMethods);
    }
}
