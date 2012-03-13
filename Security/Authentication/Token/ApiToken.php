namespace Zeroem\ApiAuthenticationBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ApiToken extends AbstractToken
{
    public $apiToken;
    public $signature;
    public $request;


    public function getCredentials() {
        return "";
    }
}