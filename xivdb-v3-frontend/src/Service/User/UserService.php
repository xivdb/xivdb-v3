<?php

namespace App\Service\User;

use App\Entity\User;
use App\Service\SSO\CsrfInvalidException;
use App\Service\SSO\DiscordSignIn;
use App\Service\SSO\SignInInterface;
use App\Service\SSO\SSOAccess;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use XIV\XivService;

class UserService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var DiscordSignIn */
    private $sso;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    /**
     * Get the user
     */
    public function user(): ?User
    {
        return $this->getUser();
    }
    
    /**
     * Get the current logged in user
     */
    public function getUser(): ?User
    {
        $session = Cookie::get('session');
        
        if (!$session) {
            return null;
        }
        
        $repo = $this->em->getRepository(User::class);
        
        /** @var User $user */
        $user = $repo->findOneBy([
            'session' => $session
        ]);
        
        return $user;
    }
    
    /**
     * Sign in
     */
    public function signIn()
    {
        return $this->sso->getLoginAuthorizationUrl()->getUrl();
    }
    
    /**
     * Authenticate
     * @throws CsrfInvalidException
     */
    public function authenticate(): User
    {
        // todo - debug this, sometimes CSRF fails, maybe implement Symfony CSRF.
        /** @var DiscordSignIn $sso */
        if (!$this->sso->isCsrfValid()) {
            //throw new CsrfInvalidException();
        }
        
        $ssoAccess = $this->sso->setLoginAuthorizationState();
        
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy([
            'email' => $ssoAccess->email
        ]);
        
        if (!$user) {
            $user = $this->createUser($this->sso::NAME, $ssoAccess);
            
            // send welcome email
            $username = ucwords($user->getUsername());
            $sdk = new XivService();
            $sdk->Email->send($user->getEmail(), "Welcome {$username}!", 'user_welcome', [
                'Username'  => $user->getUsername(),
                'Avatar'    => $user->getAvatar(),
                'SSO'       => $user->getSso(),
            ]);
        }
        
        $this->setCookie($user->getSession());
        return $user;
    }
    
    /**
     * Logout a user
     */
    public function logout()
    {
        $this->deleteCookie();
    }
    
    /**
     * Set the single sign in provider
     */
    public function setSsoProvider(SignInInterface $sso)
    {
        $this->sso = $sso;
        return $this;
    }
    
    /**
     * Set a cookie
     */
    public function setCookie($sid)
    {
        $cookie = new Cookie('session');
        $cookie
            ->setValue($sid)
            ->setMaxAge(60 * 60 * 24 * 30)
            ->setPath('/')
            ->setDomain(getenv('COOKIE_DOMAIN'))
            ->save();
    }
    
    /**
     * Delete a cookie
     */
    public function deleteCookie()
    {
        //$request->get
        $cookie = new Cookie('session');
        $cookie
            ->setValue('x')
            ->setMaxAge(-1)
            ->setPath('/')
            ->setDomain(getenv('COOKIE_DOMAIN'))
            ->save();
        
        $cookie->delete();
    }
    
    /**
     * Create a new user
     */
    public function createUser(string $sso, SSOAccess $ssoAccess): User
    {
        $user = new User();
        $user
            ->setSso($sso)
            ->setToken(json_encode($ssoAccess))
            ->setUsername($ssoAccess->username)
            ->setEmail($ssoAccess->email)
            ->setAvatar($ssoAccess->avatar);
    
        // save user
        $this->em->persist($user);
        $this->em->flush();
        
        return $user;
    }
    
    /**
     * Update a user
     */
    public function updateUser(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Download a users information
     */
    public function downloadInformation()
    {
        $user = $this->getUser();
        
        // todo - add more data to this
        $data = [];
        $data[] = "Username: {$user->getUsername()}";
        
        return implode("\n", $data);
    }
    
    public function deleteAccount()
    {
        $user = $this->getUser();
        
        if (!$user) {
            return;
        }
        
        $this->em->remove($user);
        $this->em->flush();
        return;
    }
}
