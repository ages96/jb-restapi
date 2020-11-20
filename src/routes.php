<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

return function (App $app) {
    $container = $app->getContainer();

    //memperbolehkan cors origin 
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
    $app->add(function ($req, $res, $next) {
        $response = $next($req, $res);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, token')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    });

    $app->post('/login', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $email=trim(strip_tags($input['email']));
        $val_message = [];

        if (validate("email",$email)==false){
            $val_message["email"] = "required.";
        }

        if (count($val_message)>0){
            return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
        }

        $sql = "SELECT *  FROM `user` WHERE email=:email";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("email", $email);
        $sth->execute();
        $user = $sth->fetchObject();       
        if(!$user) {
            return $this->response->withJson(['status' => 'error', 'message' => 'These credentials do not match our records username.'],400);  
        }
        $settings = $this->get('settings');       
        $token = array(
            'email' =>  $user->email
        );
        $token = JWT::encode($token, $settings['jwt']['secret'], "HS256");
        return $this->response->withJson(['status' => 'success','data'=>$user, 'token' => $token],200); 
    });

    $app->post('/createUser', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $first_name=trim(strip_tags($input['first_name']));
        $last_name=trim(strip_tags($input['last_name']));
        $email=trim(strip_tags($input['email']));
        $account=trim(strip_tags($input['account']));
        $company_id=trim(strip_tags($input['company_id']));

        $val_message = [];

        if (validate("required",$id)==false){
            $val_message["id"] = "required.";
        }

        if (validate("required",$first_name)==false){
            $val_message["first_name"] = "required.";
        }

        if (validate("required",$last_name)==false){
            $val_message["last_name"] = "required.";
        }

        if (validate("email",$email)==false){
            $val_message["email"] = "must a valid email.";
        }

        if (validate("required",$account)==false){
            $val_message["account"] = "required.";
        }

        if (validate("company_id",$company_id)==false){
            $val_message["company_id"] = "required.";
        }

        if (count($val_message)>0){
            return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
        }

        $sql = "INSERT INTO user(first_name, last_name, email, account, company_id) 
                VALUES(:first_name, :last_name, :email, :account, :company_id)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("first_name", $first_name);             
        $sth->bindParam("last_name", $last_name);            
        $sth->bindParam("email", $email);                
        $sth->bindParam("account", $account);      
        $sth->bindParam("company_id", $company_id); 
        $StatusInsert=$sth->execute();
        if($StatusInsert){
            $IdUser=$this->db->lastInsertId();     
            $settings = $this->get('settings'); 
            $token = array(
                'email' =>  $email
            );
            $token = JWT::encode($token, $settings['jwt']['secret'], "HS256");
            $dataUser=array(
                'email'=> $email
            );
            return $this->response->withJson(['status' => 'success','data'=>$dataUser, 'token'=>$token],200); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    //ambil data user berdasarkan id atau email
    $app->get("/getUser", function (Request $request, Response $response, array $args){
        $input = $request->getParams();
        $val_message = [];

        if (!isset($input["id"]) && !isset($input["email"])){
            if (empty($input["id"])&&empty($input["email"])){
                $val_message["field"] = "id or email required.";
            }
        }

        if (count($val_message)>0){
            return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
        }

        $sql = "SELECT * FROM `user` WHERE (id=:id) OR (email=:email)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("id", $input['id']);
        $stmt->bindParam("email", $input['email']);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchObject();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //Get All User
    $app->get("/getListUser", function (Request $request, Response $response, array $args){
        $sql = "SELECT * FROM user";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchAll();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //ambil data company berdasarkan id
    $app->get("/getCompany", function (Request $request, Response $response, array $args){
        $input = $request->getParams();
        $sql = "SELECT * FROM `company` WHERE (id=:id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("id", $input['id']);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchObject();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //Get All Company
    $app->get("/getListCompany", function (Request $request, Response $response, array $args){
        $sql = "SELECT * FROM company";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchAll();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //Get Budget Company
    $app->get("/getBudgetCompany", function (Request $request, Response $response, array $args){
        $input = $request->getParams();
        $sql = "SELECT * FROM `company_budget` WHERE (id=:id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("id", $input['id']);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchObject();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //Get List Budget Company
    $app->get("/getListBudgetCompany", function (Request $request, Response $response, array $args){
        $sql = "SELECT * FROM company_budget";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchAll();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    //Get Log Transaction
    $app->get("/getLogTransaction", function (Request $request, Response $response, array $args){
        $sql = "SELECT * FROM company";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $result = $stmt->fetchAll();
        if($mainCount==0) {
            return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
        }
        return $response->withJson(["status" => "success", "data" => $result], 200);
    });

    $app->post('/createCompany', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $name=trim(strip_tags($input['name']));
        $address=trim(strip_tags($input['address']));
        $sql = "INSERT INTO company(id, name, address) 
                VALUES(:id, :name, :address)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("name", $name);            
        $sth->bindParam("address", $address);
        $StatusInsert=$sth->execute();
        if($StatusInsert){
            $IdUser=$this->db->lastInsertId();     
            $settings = $this->get('settings'); 
            $data=array(
                'name'=> $name,
                'address'=>$address
            );
            return $this->response->withJson(['status' => 'success','data'=>$data],200); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    $app->post('/reimburse', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $type="R";
        $user_id=trim(strip_tags($input['user_id']));
        $amount=trim(strip_tags($input['amount']));
        $date=trim(strip_tags($input['date']));
        $sql = "INSERT INTO transaction(id, type, user_id, amount, date) 
                VALUES(:id, :type, :user_id, :amount, :date)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("type", $type);            
        $sth->bindParam("user_id", $user_id);
        $sth->bindParam("amount", $amount);
        $sth->bindParam("date", $date);
        $StatusInsert=$sth->execute();
        if($StatusInsert){
            $IdUser=$this->db->lastInsertId();     
            $settings = $this->get('settings'); 
            return $this->response->withJson(['status' => 'success','data'=>(object)null],200); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    $app->post('/disburse', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $type="C";
        $user_id=trim(strip_tags($input['user_id']));
        $amount=trim(strip_tags($input['amount']));
        $date=trim(strip_tags($input['date']));
        $sql = "INSERT INTO transaction(id, type, user_id, amount, date) 
                VALUES(:id, :type, :user_id, :amount, :date)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("type", $type);            
        $sth->bindParam("user_id", $user_id);
        $sth->bindParam("amount", $amount);
        $sth->bindParam("date", $date);
        $StatusInsert=$sth->execute();
        if($StatusInsert){
            $IdUser=$this->db->lastInsertId();     
            $settings = $this->get('settings'); 
            return $this->response->withJson(['status' => 'success','data'=>(object)null],200); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    $app->post('/close', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $type="S";
        $user_id=trim(strip_tags($input['user_id']));
        $amount=trim(strip_tags($input['amount']));
        $date=trim(strip_tags($input['date']));
        $sql = "INSERT INTO transaction(id, type, user_id, amount, date) 
                VALUES(:id, :type, :user_id, :amount, :date)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("type", $type);            
        $sth->bindParam("user_id", $user_id);
        $sth->bindParam("amount", $amount);
        $sth->bindParam("date", $date);
        $StatusInsert=$sth->execute();
        if($StatusInsert){
            $IdUser=$this->db->lastInsertId();     
            $settings = $this->get('settings'); 
            return $this->response->withJson(['status' => 'success','data'=>(object)null],200); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    //update data produk berdasarkan id
    $app->post('/updateCompany', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $name=trim(strip_tags($input['name']));
        $address=trim(strip_tags($input['address']));
        $sql = "UPDATE company SET name=:name, address=:address WHERE id=:id";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("name", $name);            
        $sth->bindParam("address", $address);
        $StatusUpdate=$sth->execute();
        if($StatusUpdate){
            return $this->response->withJson(['status' => 'success','data'=>'success update company.'],202); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error update company.'],502); 
        }
    });

    //update data user berdasarkan id
    $app->post('/updateUser', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $id=trim(strip_tags($input['id']));
        $first_name=trim(strip_tags($input['first_name']));
        $last_name=trim(strip_tags($input['last_name']));
        $sql = "UPDATE user SET first_name=:first_name, last_name=:last_name WHERE id=:id";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);             
        $sth->bindParam("first_name", $first_name);            
        $sth->bindParam("last_name", $last_name);
        $StatusUpdate=$sth->execute();
        if($StatusUpdate){
            return $this->response->withJson(['status' => 'success','data'=>'success update produk.'],202); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error update produk.'],502); 
        }
    });

    //delete company berdasarkan id
    $app->delete('/deleteCompany', function (Request $request, Response $response, array $args) {
        $args = $request->getParsedBody();
        $id = trim(strip_tags($args["id"]));
        $sql = "DELETE FROM company WHERE id=:id";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);    
        $StatusDelete=$sth->execute();
        if($StatusDelete){
            return $this->response->withJson(['status' => 'success','data'=>'success delete company.'],202); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error delete company.'],502); 
        }
    });

    //delete user berdasarkan id
    $app->post('/deleteUser', function (Request $request, Response $response, array $args) {
        $args = $request->getParsedBody();
        $id = trim(strip_tags($args["id"]));
        $sql = "DELETE FROM user WHERE id=:id";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);    
        $StatusDelete=$sth->execute();
        if($StatusDelete){
            return $this->response->withJson(['status' => 'success','data'=>'success delete user.'],202); 
        } else {
            return $this->response->withJson(['status' => 'error','data'=>'error delete user.'],502); 
        }
    });
    
};
