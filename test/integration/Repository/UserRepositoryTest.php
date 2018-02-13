<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\integration\Ingenerator\Warden\Core\Repository;


use Ingenerator\Warden\Core\Config\Configuration;
use Ingenerator\Warden\Core\Entity\SimpleUser;
use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\Repository\UserRepository;

abstract class UserRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Configuration
     */
    protected $config;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf('Ingenerator\Warden\Core\Repository\UserRepository', $this->newSubject());
    }

    public function test_it_creates_new_user_from_entity_class_configured_in_classmap()
    {
        $this->config = new Configuration(['classmap' => ['entity' => ['user' => FakeSimpleUser::class]]]);
        $this->assertInstanceOf(
            FakeSimpleUser::class,
            $this->newSubject()->newUser()
        );
    }

    public function test_saving_new_user_allocates_an_id()
    {
        $user1 = $this->newUser();
        $user2 = $this->newUser();
        $this->newSubject()->save($user1);
        $this->assertNotNull($user1->getId(), 'First saved user should have non-null ID');

        $this->newSubject()->save($user2);
        $this->assertNotNull($user2->getId(), 'Second saved user should have non-null ID');
        $this->assertNotEquals($user1->getId(), $user2->getId(), 'Generated IDs should be unique');
    }

    public function test_saving_existing_user_does_not_change_id()
    {
        $this->given_saved_users($this->newUser(['email' => 'existing-user@foo.net.zz']));
        $subject     = $this->newSubject();
        $user        = $subject->loadByEmail('existing-user@foo.net.zz');
        $original_id = $user->getId();
        $subject->save($user);
        $this->assertSame($original_id, $user->getId(), 'Existing ID should be retained on save');
    }

    /**
     * @expectedException \Ingenerator\Warden\Core\Repository\DuplicateUserException
     */
    public function test_throws_duplicate_user_if_attempting_to_create_user_with_already_registered_email()
    {
        $email = uniqid('duplicate').'@bar.ban';
        $this->newSubject()->save($this->newUser(['email' => $email]));
        $this->newSubject()->save($this->newUser(['email' => $email]));
    }

    /**
     * @expectedException \Ingenerator\Warden\Core\Repository\DuplicateUserException
     */
    public function test_throws_duplicate_user_if_attempting_to_update_user_to_other_users_email()
    {
        $other_user = $this->newUser();
        $this_user  = $this->newUser();
        $this->given_saved_users($other_user, $this_user);

        $this_user->setEmail($other_user->getEmail());
        $this->newSubject()->save($this_user);
    }

    public function test_can_update_existing_user_with_same_email()
    {
        $email = uniqid('not-duplicate').'@bar.ban';
        $user  = $this->newUser(['email' => $email]);
        $this->given_saved_users($user);
        $user->setPasswordHash('stuff');
        $this->newSubject()->save($user);
    }

    public function test_it_returns_null_when_loading_user_with_unknown_id()
    {
        $this->assertNull($this->newSubject()->loadById(1234));
    }

    public function test_it_can_load_saved_user_by_id()
    {
        $saved_user = $this->newUser();
        $this->given_saved_users($this->newUser(), $saved_user);
        $loaded_user = $this->newSubject()->loadById($saved_user->getId());
        $this->assertSame($saved_user, $loaded_user);
    }

    public function test_it_returns_null_when_loading_user_with_unknown_email()
    {
        $this->given_saved_users($this->newUser(['email' => uniqid('dummy-user').'@foo.net']));
        $this->assertNull($this->newSubject()->loadByEmail(uniqid().'@unknown.com'));
    }

    public function test_it_can_load_saved_user_by_email()
    {
        $email      = uniqid('saved-user').'@bax.con';
        $saved_user = $this->newUser(['email' => $email]);
        $this->given_saved_users($this->newUser(), $saved_user);
        $loaded_user = $this->newSubject()->loadByEmail($email);
        $this->assertSame($saved_user, $loaded_user);
    }

    protected function newUser(array $values = [])
    {
        $data = array_merge(
            [
                'email'         => uniqid().'@bar.net',
                'password_hash' => uniqid(),
                'id'            => NULL,
            ],
            $values
        );

        $user = $this->newSubject()->newUser();

        $refl = new \ReflectionClass($user);
        foreach ($data as $field => $value) {
            $property = $refl->getProperty($field);
            $property->setAccessible(TRUE);
            $property->setValue($user, $value);
        }

        return $user;
    }

    public function setUp()
    {
        parent::setUp();
        $this->config = new Configuration([]);
    }

    /**
     * @return UserRepository
     */
    abstract protected function newSubject();

    /**
     * @param User $other_user,...
     */
    protected function given_saved_users($other_user)
    {
        $repo = $this->newSubject();
        foreach (func_get_args() as $user) {
            $repo->save($user);
        }
    }
}


class FakeSimpleUser extends SimpleUser
{
}
