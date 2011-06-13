sailthru-php5-client
====================

A simple client library to remotely access the `Sailthru REST API` as per [http://docs.sailthru.com/api](http://docs.sailthru.com/api)

It can make requests to following [API calls](http://docs.sailthru.com/api):

* [email](http://docs.sailthru.com/api/email)
* [send](http://docs.sailthru.com/api/send)
* [blast](http://docs.sailthru.com/api/blast)
* [template](http://docs.sailthru.com/api/template)
* [list](http://docs.sailthru.com/api/list)
* [contacts](http://docs.sailthru.com/api/contacts)
* [content](http://docs.sailthru.com/api/content)
* [alert](http://docs.sailthru.com/api/alert)
* [stats](http://docs.sailthru.com/api/stats)
* [purchase](http://docs.sailthru.com/api/purchase)
* [job](http://docs.sailthru.com/api/job)
* [horizon](http://docs.sailthru.com/api/horizon)


 Examples
 ---------

### Constructor

 	$api_key = "api_key";
	$api_secret = 'secret';
 	$sailthruClient = new Sailthru_Client($api_key, $api_secret);


### [send](http://docs.sailthru.com/api/send)

 	//send
 	$response = $sailthruClient->send('temlate-name', 'praj@sailthru.com', array('name' => 'unix'), array('test' => 1));

 	//multi send
	$response = $sailthruClient->multisend('default', 'abc@xyz.com,praj@sailthru.com', array('name' => 'unix'), array(), array('test' => 1));

	//get send
	$response = $sailthruClient->getSend("TUMVqWdj2exnAAV-");

	//cancel send
	$response = $sailthruClient->cancelSend("TUMYT2dj2fl1AABD");

### [email](http://docs.sailthru.com/api/email)

	//get email information of a user
	$response = $sailthruClient->getEmail("praj@sailthru.com");

	//update user info
	$email = 'praj@sailthru.com';
	$vars = array('name' => 'Prajwal Tuladhar');
	$lists = array('list-a' => 1, 'list-b' => 1);
	$templates = array('template-1' => 1, 'template-2' => 2);
	$verified = 1;
	$response = $sailthruClient->setEmail($email, $vars, $lists, $templates, $verified);

### [blast](http://docs.sailthru.com/api/blast)

    //Get Blast information
    $blast_id = 52424;
    $response = $sailthruClient->getBlast($blast_id);

	//schedule blast
	$blast_name = 'test_blast1';
    $list = 'default';
    $schedule_time = '+1 days';
    $from_name = 'Prajwal Tuladhar';
    $from_email = 'praj@sailthru.com';
    $subject = "Hey what's up!";
    $content_html = "<b>Lorem ipsum dolor si</b>";
    $content_text = strip_tags($content_html);

    $response = $sailthruClient->scheduleBlast($blast_name, $list, $schedule_time, $from_name, $from_email, $subject, $content_html, $content_text);

    //schedule blast from template
    $template = 'default';
    $list = 'default';
    $schedule_time = 'now';
    $options  = array();
    #$response = $sailthruClient->scheduleBlastFromTemplate($template, $list, $schedule_time, $options);

    //schedule blast from previous blast
    //Note: if blast_id is invalid, request won't work
    $_blast_id = '110065';
    $_schedule_time = 'now';
    $_options = array(
        'vars' => array(
            'my_var' => '3y6366546363',
            'my_var2' => array(7,8,9),
            'my_var3' => array('president' => 'obama', 'nested' => array('vp' => 'palin'))),
    );
    $response = $sailthruClient->scheduleBlastFromBlast($_blast_id, $_schedule_time, $_options);



    //update blast
    $blast_id = 46513;
    $blast_name = 'test_blast88';
    $response = $sailthruClient->updateBlast($blast_id, $blast_name, $list, $schedule_time, $from_name, $from_email, $subject, $content_html, $content_text);

    //cancel scheduled blast
    $response = $sailthruClient->cancelBlast($blast_id);

    //delete blast
    $response = $sailthruClient->deleteBlast($blast_id);

### [list](http://docs.sailthru.com/api/list)

    //get metadata for all lists
    $lists_metadata = $sailthruClient->getLists();

	//download a list
	$list = 'default';
	$response = $sailthruClient->getList($list, "txt");

	//saves /updates a list
	$emails = 'more@abc.com, less@xyz.com';
	$response = $sailthruClient->saveList($list, $emails);

	//delete a list
	$response = $sailthruClient->deleteList($list);

### [template](http://docs.sailthru.com/api/template)

    //get meta-data of all exisiting templates
    $response = $sailthruClient->getTemplates();

    //get information of a given template
    $template = 'welcome-template';
    $response = $sailthruClient->getTemplate($template);

    //get information of a template from it's revision id
    $revision_id = 45204;
    $response = $sailthruClient->getTemplateFromRevision($revision_id);

    //create new template or update existing one
    $template = 'new-template';
    $options = array(
        'from_name' => 'Sailthru Support',
        'from_email' => 'support@sailthru.com',
        'content_html' => '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec qu</p>',
        'subject' => 'Sailthru Support'
    );
    $response = $sailthruClient->saveTemplate($template, $options);

    //save template from existing revision id
    $template = 'welcome-template';
    $revision_id = 45204;
    $response = $sailthruClient->saveTemplateFromRevision($template, $revision_id);

    //delete existing template
    $template = 'welcome-template';
    $response = $sailthruClient->deleteTemplate($template);

### [contacts](http://docs.sailthru.com/api/contacts)

	//import contacts
	$response = $sailthruClient->importContacts('some-email@gmail.com', "your-super-secret-password-which-we-will-never-store");

### [content](http://docs.sailthru.com/api/content)

	//push content
	$title = 'hello world';
	$url = 'http://example.com/product-url';
	$response = $sailthruClient->pushContent($title, $url);

	//another push content example
	$title = 'hello world';
	$url = 'http://example.com/product-url';
	$date = null;
	$tags = array("blue", "red", "green");
	$vars = array('vars' => array('price' => 17299));
	$response = $sailthruClient->pushContent($title, $url, $date, $tags, $vars);

### [alert](http://docs.sailthru.com/api/alert)

	//Retrieve a user's alert settings.
	$email = 'praj@sailthru.com';
	$response = $sailthruClient->getAlert($email);

	//saves an alert for a user
	$email = 'praj@sailthru.com';
	$alert_type = 'realtime';
	$options = array(
        'match' => array(
            'type' => array(
                'shoes', 'shirts'
            )
        ),
        'min' => array(
            'price' => 3000
        ),
        'tags' => array('blue', 'red')
    );
    $when = null;
    $template = 'my-template';
    $response = $sailthruClient->saveAlert($email, $alert_type, $template, $when, $options);


    //deletes an alert
    $email = 'praj@sailthru.com';
    $alert_id = '4d463bad6763d90e0e000581';
    $response = $sailthruClient->deleteAlert($email, $alert_id);

### [purchase](http://docs.sailthru.com/api/purchase)

	//post purchase
	$email = 'praj@sailthru.com';
	$items = array(
		array('id' => 11, 'price' => 26262, 'qty' => '11', 'url' => 'http://example.com/234/high-impact-water-bottle', 'title' => 'High-Impact Water Bottle'),
		array('id' => 171, 'price' => 262, 'qty' => '18', 'url' => 'http://xyz.com/abc', 'title' => 'some title2')
	);
	$response = $sailthruClient->purchase($email, $items);

### [stats](http://docs.sailthru.com/api/stats)

	//get list stats
	$response = $sailthruClient->stats_list();

	//get blast stats
	$blast_id = 6752;
	$response = $sailthruClient->stats_blast($blast_id);

### [horizon](http://docs.sailthru.com/api/horizon)

	//gets horizon data for a user
	$email = ''praj@sailthru.com;
	$hid_only = false;
	$response = $sailthruClient->getHorizon($email, $hid_only);

	//sets horizon user data
	$email = 'praj@sailthru.com';
	$tags = array('blue', 'red', 'green');
	$response = $sailthruClient->setHorizon($email, $tags);

	//set horizon cookie
	$email = 'praj@sailthru.com';
	$sailthruClient->setHorizonCookie($email);

### [job](http://docs.sailthru.com/api/job)

    //get the status of a job
    $job_id = '4dd58f036803fa3b5500000b';
    $response = $sailthruClient->getJobStatus($job_id);

    # process import job for email string
    $list = 'test-list';
    $emails = 'a@a.com,b@b.com';
    $response = $sailthruClient->processImportJob($list, $emails);

    # process import job from CSV or text file
    $list = 'test-list';
    $source_file = '/home/praj/Desktop/emails.txt';
    $response = $sailthruClient->processImportJobFromFile($list, $source_file);

    # process snapshot job
    $query = array();
    $report_email = 'praj@sailthru.com';
    $postback_url = 'http://example.com/reports/snapshot_postback';
    response = $sailthruClient->processSnapshotJob($query);

    # process export list job
    $list = 'test-list';
    $response = $sailthruClient->processExportListJob($list);

### [postbacks](http://docs.sailthru.com/api/postbacks)

	//recieve verify post
	$sailthruClient->receiveVerifyPost();

	//recieve optout post
	$sailthruClient->receiveOptoutPost();
