<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_token_model extends My_Model
{
    const TABLE_NAME = TBL_PREF . 'tokens';

    const TOKEN_COOKIE_NAME = 'chat_token';
    const TOKEN_TTL = 7200;

    protected $token;
    protected $expire;
    protected $create_at;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    public function getExpireDate(): DateTime
    {
        return new DateTime($this->expire);
    }

    public function getCreatedDate(): DateTime
    {
        return new DateTime($this->create_at);
    }

    private function generateToken($userID): string
    {
        return md5($userID . date('r'));
    }

    public static function validate(string $token, int $userID): bool
    {
        $CI = & get_instance();

        /** @var CI_DB_result $user */
        $user = $CI->db->select('id')
            ->where('token', $token)
            ->where('expire > NOW()')
            ->get(self::TABLE_NAME);

        return $user->num_rows() > 0 ? (int)$user->row()->id === $userID : false;
    }

    /**
     * @param int $uid
     * @return string
     */
    public function updateToken(int $uid): string
    {
        $result = $this->db->get_where(self::TABLE_NAME, ['id' => $uid])->result_array()[0];
        $token = $result['token'];

        if ($token === null || strtotime($result['expire']) < time()) {
            $token = $this->generateToken($uid);

            $this->db->query(
                "INSERT INTO shopsy_tokens (id, token) VALUES (?, ?) ".
                "ON DUPLICATE KEY UPDATE token = ?",
                [$uid, $token, $token]
            );
        }

        $this->db->query(
            'UPDATE shopsy_tokens 
                SET updated_at = CURRENT_TIMESTAMP(), expire = DATE_ADD(updated_at, INTERVAL ? SECOND) 
                WHERE id = ?',
            [self::TOKEN_TTL, $uid]
        );

        return $token;
    }
}