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
            $log = [
                "request"=>$input,
                "response"=>['status' => 'failed','message'=>$val_message]
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
        }

        $sql = "SELECT *  FROM `user` WHERE email=:email";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("email", $email);
        $sth->execute();
        $user = $sth->fetchObject();       
        if(!$user) {
            $log = [
                "request"=>$input,
                "response"=>['status' => 'error', 'message' => 'These credentials do not match our records username.']
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'error', 'message' => 'These credentials do not match our records username.'],400);  
        }
        $settings = $this->get('settings');       
        $token = array(
            'email' =>  $user->email
        );
        $token = JWT::encode($token, $settings['jwt']['secret'], "HS256");
        $log = [
            "request"=>$input,
            "response"=>['status' => 'success','data'=>$user, 'token' => $token]
        ];
        $this->logger->info(json_encode($log));
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

        if (validate("number",$id)==false){
            $val_message["id"] = "must be number.";
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

        if (validate("number",$company_id)==false){
            $val_message["company_id"] = "must be a number.";
        }

        if (count($val_message)>0){
            $log = [
                "request"=>$input,
                 "response"=>['status' => 'failed','message'=>$val_message]
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
        }

        $sql = "SELECT * FROM `user` WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        if($mainCount>0) {
            $log = [
                "request"=>$input,
                 "response"=>['status' => 'error', 'message' => 'user_id already exist.']
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'error', 'message' => 'user_id already exist.'],400);
        }

        $sql = "SELECT * FROM `company` WHERE id=:id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("id", $company_id);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $user = $stmt->fetchObject();
        if($mainCount==0) {
            $log = [
                "request"=>$input,
                 "response"=>['status' => 'error', 'message' => 'company_id not found.']
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'error', 'message' => 'company_id not found.'],404);
        }

        $sql = "INSERT INTO user(id,first_name, last_name, email, account, company_id) 
                VALUES(:id,:first_name, :last_name, :email, :account, :company_id)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $id);    
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
            $log = [
                "request"=>$input,
                 "response"=>['status' => 'success','data'=>$dataUser, 'token'=>$token]
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'success','data'=>$dataUser, 'token'=>$token],200); 
        } else {
            $log = [
                "request"=>$input,
                 "response"=>['status' => 'error','data'=>'error insert user.']
            ];
            $this->logger->info(json_encode($log));
            return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
        }
    });

    $app->group('/api', function () use ($app) {

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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        //Get All User
        $app->get("/getListUser", function (Request $request, Response $response, array $args){
            $input = $request->getParams();
            $sql = "SELECT * FROM user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $result = $stmt->fetchAll();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        //Get All Company
        $app->get("/getListCompany", function (Request $request, Response $response, array $args){
            $input = $request->getParams();
            $sql = "SELECT * FROM company";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $result = $stmt->fetchAll();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        //Get Budget Company
        $app->get("/getBudgetCompany", function (Request $request, Response $response, array $args){
            $input = $request->getParams();
            $sql = "SELECT company.name as company_name, company_budget.amount as company_budget_amount FROM `company` INNER JOIN company_budget ON company_budget.company_id = company_id WHERE (company.id=:id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $input['id']);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $result = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'No result data based on that company id.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'No result data based on that company id.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        //Get List Budget Company
        $app->get("/getListBudgetCompany", function (Request $request, Response $response, array $args){
            $input = $request->getParams();
            $sql = "SELECT company.name as company_name, company_budget.amount as company_budget_amount FROM `company` INNER JOIN company_budget ON company_budget.company_id = company_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $result = $stmt->fetchAll();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        //Get Log Transaction
        $app->get("/getLogTransaction", function (Request $request, Response $response, array $args){
            $input = $request->getParams();
            $sql = "SELECT concat(user.first_name, ' ', user.last_name) as fullname, user.account as user_account, company.name as company_name, transaction.type as transaction_type, transaction.date as transaction_date, transaction.amount as transaction_amount, company_budget.amount as remaining_amount FROM user LEFT JOIN company on company.id = user.company_id INNER JOIN transaction ON transaction.user_id INNER JOIN company_budget ON company.id = company_budget.company_id ORDER BY transaction.date desc";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $result = $stmt->fetchAll();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'no result data.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.'],404); 
            }
                $log = [
                    "request"=>$input,
                     "response"=>["status" => "success", "data" => $result]
                ];
                $this->logger->info(json_encode($log));
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });

        $app->post('/createCompany', function (Request $request, Response $response, array $args) {
            $input = $request->getParsedBody();
            $budget = 1000000;
            $id=trim(strip_tags($input['id']));
            $name=trim(strip_tags($input['name']));
            $address=trim(strip_tags($input['address']));
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "must be number.";
            }

            if (validate("required",$name)==false){
                $val_message["name"] = "required.";
            }

            if (validate("required",$address)==false){
                $val_message["address"] = "required.";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }
            
            $sql = "INSERT INTO company(id, name, address) 
                    VALUES(:id, :name, :address)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);             
            $sth->bindParam("name", $name);            
            $sth->bindParam("address", $address);
            $sth->execute();

            $sql = "INSERT INTO company_budget(id, company_id, amount) 
                    VALUES(:id, :company_id, :amount)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $company_id);             
            $sth->bindParam("company_id", $company_id);            
            $sth->bindParam("amount", $budget);
            $StatusInsert=$sth->execute();

            if($StatusInsert){
                $IdUser=$this->db->lastInsertId();     
                $settings = $this->get('settings'); 
                $data=array(
                    'company_name'=> $name,
                    'company_address'=> $address,
                    'company_budget'=> $budget
                );
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'success','data'=>$data]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'success','data'=>$data],200); 
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error','data'=>'error insert company.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error','data'=>'error insert company.'],502); 
            }
        });

        $app->post('/reimburse', function (Request $request, Response $response, array $args) {
            $input = $request->getParsedBody();
            $id=trim(strip_tags($input['id']));
            $type="R";
            $user_id=trim(strip_tags($input['user_id']));
            $amount=trim(strip_tags($input['amount']));
            $date=trim(strip_tags($input['date']));
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "must be number.";
            }

            if (validate("number",$user_id)==false){
                $val_message["user_id"] = "must be number.";
            }

            if (validate("number",$amount)==false){
                $val_message["amount"] = "must be number.";
            }

            if (validate("date",$date)==false){
                $val_message["date"] = "Invalid format, it should be 'Y-m-d H:i:s' (1996-12-29 00:00:00).";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `transaction` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            if($mainCount>0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'id transaction already exist.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'id transaction already exist.'],404);
            }

            $sql = "SELECT * FROM `user` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'user_id not found.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'user_id not found.'],404);
            }

            $sql = "SELECT * FROM `company_budget` WHERE company_id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user->company_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $budget_cp=$stmt->fetchObject();
            if($mainCount>0) {
                $budget_cp=$budget_cp->amount;
                if ($amount>$budget_cp){
                      return $this->response->withJson(['status' => 'error', 'message' => 'Amount exceeded.'],404);  
                } else {
                    $substract = $budget_cp - $amount;
                    $sql = "UPDATE company_budget SET amount=:amount WHERE company_id=:company_id";
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam("company_id", $user->company_id);             
                    $sth->bindParam("amount", $substract);
                    $StatusUpdate=$sth->execute();
                }
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company'],404);
            }

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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'success','data'=>(object)null]
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'success','data'=>(object)null],200); 
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error','data'=>'error insert user.']
                ];
                $this->logger->info(json_encode($log));
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
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "must be number.";
            }

            if (validate("number",$user_id)==false){
                $val_message["user_id"] = "must be number.";
            }

            if (validate("number",$amount)==false){
                $val_message["amount"] = "must be number.";
            }

            if (validate("date",$date)==false){
                $val_message["date"] = "Invalid format, it should be 'Y-m-d H:i:s' (1996-12-29 00:00:00).";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `transaction` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            if($mainCount>0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'id transaction already exist.']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error', 'message' => 'id transaction already exist.'],404);
            }

            $sql = "SELECT * FROM `user` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'user_id not found.']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error', 'message' => 'user_id not found.'],404);
            }

            $sql = "SELECT * FROM `company_budget` WHERE company_id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user->company_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $budget_cp=$stmt->fetchObject();
            if($mainCount>0) {
                $budget_cp=$budget_cp->amount;
                if ($amount>$budget_cp){
                    $log = [
                        "request"=>$input,
                         "response"=>['status' => 'error', 'message' => 'Amount exceeded.']
                    ];
                    $this->logger->info(json_encode($log)); 
                    return $this->response->withJson(['status' => 'error', 'message' => 'Amount exceeded.'],404);  
                } else {
                    $substract = $budget_cp - $amount;
                    $sql = "UPDATE company_budget SET amount=:amount WHERE company_id=:company_id";
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam("company_id", $user->company_id);             
                    $sth->bindParam("amount", $substract);
                    $StatusUpdate=$sth->execute();
                }
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company'],404);
            }

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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'success','data'=>(object)null]
                ];
                $this->logger->info(json_encode($log));  
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
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "must be number.";
            }

            if (validate("number",$user_id)==false){
                $val_message["user_id"] = "must be number.";
            }

            if (validate("number",$amount)==false){
                $val_message["amount"] = "must be number.";
            }

            if (validate("date",$date)==false){
                $val_message["date"] = "Invalid format, it should be 'Y-m-d H:i:s' (1996-12-29 00:00:00).";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));  
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `transaction` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            if($mainCount>0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'id transaction already exist.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'id transaction already exist.'],404);
            }

            $sql = "SELECT * FROM `user` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'user_id not found.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'user_id not found.'],404);
            }

            $sql = "SELECT * FROM `company_budget` WHERE company_id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $user->company_id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $budget_cp=$stmt->fetchObject();
            if($mainCount>0) {
                $budget_cp=$budget_cp->amount;
                $addition = $budget_cp + $amount;
                $sql = "UPDATE company_budget SET amount=:amount WHERE company_id=:company_id";
                $sth = $this->db->prepare($sql);
                $sth->bindParam("company_id", $user->company_id);             
                $sth->bindParam("amount", $addition);
                $StatusUpdate=$sth->execute();
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error', 'message' => 'There is no company budget for this user company. Please add the data company budget first for this user company'],404);
            }

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
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'success','data'=>(object)null]
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'success','data'=>(object)null],200); 
            } else {
                $log = [
                    "request"=>$input,
                     "response"=>['status' => 'error','data'=>'error insert user.']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error','data'=>'error insert user.'],502); 
            }
        });

        //update data produk berdasarkan id
        $app->post('/updateCompany', function (Request $request, Response $response, array $args) {
            $input = $request->getParsedBody();
            $id=trim(strip_tags($input['id']));
            $name=trim(strip_tags($input['name']));
            $address=trim(strip_tags($input['address']));
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "Must be a number.";
            }

            if (validate("required",$name)==false){
                $val_message["name"] = "required.";
            }

            if (validate("required",$address)==false){
                $val_message["address"] = "required.";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `company` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'error', 'message' => 'id company not found.']
                ];
                $this->logger->info(json_encode($log)); 
                return $this->response->withJson(['status' => 'error', 'message' => 'id company not found.'],404);
            }

            $sql = "UPDATE company SET name=:name, address=:address WHERE id=:id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);             
            $sth->bindParam("name", $name);            
            $sth->bindParam("address", $address);
            $StatusUpdate=$sth->execute();
            if($StatusUpdate){
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'success','data'=>'success update company.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'success','data'=>'success update company.'],202); 
            } else {
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'error','data'=>'error update company.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error','data'=>'error update company.'],502); 
            }
        });

        //update data user berdasarkan id
        $app->post('/updateUser', function (Request $request, Response $response, array $args) {
            $input = $request->getParsedBody();
            $id=trim(strip_tags($input['id']));
            $first_name=trim(strip_tags($input['first_name']));
            $last_name=trim(strip_tags($input['last_name']));

            if (validate("number",$id)==false){
                $val_message["id"] = "must be a number.";
            }

            if (validate("required",$first_name)==false){
                $val_message["first_name"] = "required.";
            }

            if (validate("required",$last_name)==false){
                $val_message["last_name"] = "required.";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `user` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'error', 'message' => 'id user not found.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'id user not found.'],404);
            }

            $sql = "UPDATE user SET first_name=:first_name, last_name=:last_name WHERE id=:id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);             
            $sth->bindParam("first_name", $first_name);            
            $sth->bindParam("last_name", $last_name);
            $StatusUpdate=$sth->execute();
            if($StatusUpdate){
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'success','data'=>'success update user.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'success','data'=>'success update user.'],202); 
            } else {
                $log = [
                    "request"=>$input,
                    "response"=>['status' => 'error','data'=>'error update user.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error','data'=>'error update user.'],502); 
            }
        });

        //delete company berdasarkan id
        $app->delete('/deleteCompany', function (Request $request, Response $response, array $args) {
            $args = $request->getParsedBody();
            $id = trim(strip_tags($args["id"]));
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "Must be a number.";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `company` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'error', 'message' => 'id company not found.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'id company not found.'],404);
            }

            $sql = "DELETE FROM company_budget WHERE company_id=:id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);    
            $StatusDelete=$sth->execute();

            $sql = "DELETE FROM company WHERE id=:id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);    
            $StatusDelete=$sth->execute();
            if($StatusDelete){
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'success','data'=>'success delete company.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'success','data'=>'success delete company.'],202); 
            } else {
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'error','data'=>'error delete company.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error','data'=>'error delete company.'],502); 
            }
        });

        //delete user berdasarkan id
        $app->post('/deleteUser', function (Request $request, Response $response, array $args) {
            $args = $request->getParsedBody();
            $id = trim(strip_tags($args["id"]));
            $val_message = [];

            if (validate("number",$id)==false){
                $val_message["id"] = "Must be a number.";
            }

            if (count($val_message)>0){
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'failed','message'=>$val_message]
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'failed','message'=>$val_message],400); 
            }

            $sql = "SELECT * FROM `user` WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $user = $stmt->fetchObject();
            if($mainCount==0) {
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'error', 'message' => 'id user not found.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error', 'message' => 'id user not found.'],404);
            }

            $sql = "DELETE FROM user WHERE id=:id";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("id", $id);    
            $StatusDelete=$sth->execute();
            if($StatusDelete){
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'success','data'=>'success delete user.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'success','data'=>'success delete user.'],202); 
            } else {
                $log = [
                    "request"=>$args,
                    "response"=>['status' => 'error','data'=>'error delete user.']
                ];
                $this->logger->info(json_encode($log));
                return $this->response->withJson(['status' => 'error','data'=>'error delete user.'],502); 
            }
        });

    });

};
