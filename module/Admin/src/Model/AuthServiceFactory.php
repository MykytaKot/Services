<?
namespace Admin\Model;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Core\CollectionMap;
use Admin\Model\AuthService;

class AuthServiceFactory implements FactoryInterface {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
       
        return new AuthService();
    }

}