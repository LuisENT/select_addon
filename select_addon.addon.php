<?php
	
    /**
     * @select_addon.addon.php
     * @author KDP (kdp7584@gmail.com)
     * @brief Select Addon
     **/
	
	if(!defined("__XE__")) exit();
	
	/**
	 * @admin page
	 **/
	
	 if(Context::get('module') == 'admin' && Context::get('act') == 'dispAddonAdminSetup' && Context::get('selected_addon') == 'select_addon')
	{
		if($addon_info->readme_open=='Y'){
			//remove first_user alert
			$stroutput = str_replace('<input type="text" name="information_installed" id="information_installed" value="">','<a style>[사용자 문서]</a><br>처음사용자용 문서 (Contact : <a href="mailto:kdp7584@gmail.com">메일 보내기</a>, <a href="https://www.xpressengine.com/index.php?mid=download&module=profile&act=dispProfileView&member_srl=22843987">프로필</a>).',$output);
			$output = $stroutput;
			
			//user document
			$user_doc='본 애드온은 클릭 몇번으로 간단한 애드온을 만들어 볼 수 있는 애드온입니다.'."<br>".'필수적으로 설정해야 할 목록은 다음과 같습니다.'."<br>".'->애드온 시점, 애드온이 활성될 시점, 행동 목록중 하나(결과확인)'."<br><br>".'리스트 전체에 행동을 적용하려면 리스트->인식으로 설정하셔야 합니다.'.'<br><br>'.'예제를 통해 더욱 쉽게 이해하세요.<br><a style>1.게시글 리스트제목을 전부 [게시판 제목]으로 바꾸기</a><br>비교 = AND<br>애드온 시점 = 모듈의 동작 이후<br>애드온이 활성될 시점 = 게시판 내용 보기<br>리스트 = 인식<br>제목 = [게시판 제목]<br><br><a style>2.24시간동안의 게시글을 공지사항 등록하기</a><br>비교 = A가 B보다 크다<br>대상A = 글등록일(#$@%@%)<br>대상B = $시전 날짜<br>대상B 설정 = 24<br>애드온 시점 = 모듈의 동작 이후<br>애드온이 활성될 시점 = 게시판 내용 보기<br>리스트 = 인식<br>공지사항 등록 = 사용<br><br>
			<a style>기본함수목록</a><br>[THIS_DATE] 오늘의 날짜<br>[THIS_REGDATE] 글 등록일<br>[THIS_TITLE] 글 제목<br>[TODAY_COUNT] 오늘 등록된 글 카운트';
			$stroutput = str_replace('<input type="text" name="user_readme" id="user_readme" value="">',$user_doc,$output);
			$output = $stroutput;
		}
		
		//member_group
		$oGroup_list='';
		$oMemberModel = getModel('member');
		$oGroup_set=$oMemberModel->getGroups($this->site_srl);
		foreach($oGroup_set as $i => $var)
		{
			$oGroup_list = $oGroup_list.$var->group_srl.'('.$var->title.')'.' ';
		}
		$stroutput = str_replace('<input type="text" name="show_member_list" id="show_member_list" value="">',$oGroup_list,$output);
		$output = $stroutput;
		
		//document_count
		if($addon_info->today_mid != NULL)
		{
			$oGroup_list='';
			$oModuleModel = getModel('module');
			$args->module_srl = $oModuleModel->getModuleSrlByMid($addon_info->today_mid);
			$args->r_regdate = date("Ymd");
			$tmp_output = executeQuery('addons.select_addon.getNewDocument', $args);
			foreach($tmp_output as $i => $var){}
			$oGroup_list = count($var);
			$stroutput = str_replace('<input type="text" name="show_today_document" id="show_today_document" value="">',$oGroup_list,$output);
			$output = $stroutput;
		}
		return;
	}
		
	$resmethod = Context::getResponseMethod();
	$document_insert = '';
	$document_srl = Context::get('document_srl');
	$logged_info = Context::get('logged_info');
	$oDocument = Context::get('oDocument');
	$oModuleModel = getModel('module');
	$oDocumentModel = &getModel('document');
	$oCommentModel = &getModel('comment');
	$notice_list = Context::get('notice_list');
	$document_list = Context::get('document_list');
	$comment_list = $oCommentModel->getCommentList($document_srl);
	$lang_type = Context::getLangType();

	/**
	 * @get document list
	 **/
	
	if(!$addon_info->list_target==NULL && Context::get('mid'))
	{
		
		if(!$addon_info->list_target=='date')
		{
			$search_target = array('monthago' => 'month', 'weekago' => 'week', 'dayago' => 'day', 'hoursago' => 'hours', 'secondsago' => 'seconds');
			$search_target2 = array('monthago' => 'Ym', 'weekago' => 'Ymd','dayago' => 'Ymd', 'hoursago' => 'YmdH', 'secondsago' => 'YmdHis');
		}
		
		if($addon_info->list_target=='date')
			$target_date = date("Ymd");
		else
			$target_date = date("".$search_target2[$addon_info->list_target]."", strtotime("-".$addon_info->target_date_target." ".$search_target[$addon_info->list_target].""));
		
		$args->module_srl = $oModuleModel->getModuleSrlByMid(Context::get('mid'));
		$args->r_regdate = $target_date;
		
		if($addon_info->list_target=='date')
			$tmp_output = executeQuery('addons.select_addon.getNewDocument', $args);
		else
			$tmp_output = executeQuery('addons.select_addon.getAgoDocument', $args);
		
		$document_insert = $tmp_output;
	}
	
	/**
	 * @check document conditional sentence
	 **/
	
	//check document attribute
	if($document_insert==NULL)
	{
		if($addon_info->list_accept=='')
		{
			if($addon_info->doc_attr == 'is_notice')
			{
				$args->document_srl = Context::get('document_srl');
				$tmp_output = executeQuery('addons.select_addon.getisNotice', $args);
				if($tmp_output->toBool())
					$target_c=$tmp_output->data->is_notice;
				if($target_c=='N') return;
			}
			elseif($addon_info->doc_attr == 'comment_status')
			{
				$args->document_srl = Context::get('document_srl');
				$tmp_output = executeQuery('addons.select_addon.getCommentStatus', $args);
				if($tmp_output->toBool())
					$target_c=$tmp_output->data->comment_status;
				if($target_c=='N') return;
			}
			elseif($addon_info->doc_attr == 'status')
			{
				$args->document_srl = Context::get('document_srl');
				$tmp_output = executeQuery('addons.select_addon.getStatus', $args);
				if($tmp_output->toBool())
					$target_c=$tmp_output->data->status;
				if($status=='PUBLIC') return;
			}
			elseif($addon_info->doc_attr == 'title_bold')
			{
				$args->document_srl = Context::get('document_srl');
				$tmp_output = executeQuery('addons.select_addon.getTitlebold', $args);
				if($tmp_output->toBool())
					$target_c=$tmp_output->data->title_bold;
				if($title_bold=='N') return;
			}
		}
	}
	
	/**
	 * @target variable
	 **/
	
	$search_target = array('a', 'b');
	
	foreach($search_target as $var)
	{
		$target_value = 'calc_target_'.$var;
		$target_target_value = 'target_'.$var.'_target';
		$target_numberic_value = 'target_a_numberic'.$var;
		$target_text = 'target_'.$var.'_text';
		
		if($addon_info->list_accept=='')
		{
			if($addon_info->$target_value == 'regdate')
			{
				if(!$document_insert==NULL)
				{
					$args->document_srl = Context::get('document_srl');
					$tmp_output = executeQuery('addons.select_addon.getDocumentRegdate', $args);
					if($tmp_output->toBool())
						$tr_val=$tmp_output->data->regdate;
				}
			}
			elseif($addon_info->$target_value == 'comment_count')
			{
				$args->document_srl = Context::get('document_srl');
				$tmp_output = executeQuery('addons.select_addon.getCommentCount', $args);
				if($tmp_output->toBool())
					$tr_val=$tmp_output->data->count;
			}
			elseif($addon_info->$target_value=='readed_count')
			{
				$tr_val=$oDocument->get('readed_count');
			}
			elseif($addon_info->$target_value=='blamed_count')
			{
				$tr_val=$oDocument->get('blamed_count');
			}
			elseif($addon_info->$target_value=='title_len')
			{
				$tr_val=Context::get('title_len');
			}
			elseif($addon_info->$target_value=='odoc_ip_address')
			{
				$tr_val=$oDocument->variables['ip_address'];
			}
			elseif($addon_info->$target_value=='document_srl')
			{
				$tr_val=$oDocument->variables['document_srl'];
			}
			elseif($addon_info->$target_value=='odoc_member_srl')
			{
				$tr_val=$oDocument->variables['member_srl'];
			}
		}
		
		
		if($addon_info->$target_value == 'text')
		{
			$tr_val=$addon_info->$target_text;
		}
		elseif($addon_info->$target_value == 'browser_title')
		{
			$tr_val=Context::getBrowserTitle();
		}
		elseif($addon_info->$target_value == 'member_group')
		{
			$tr_val=$logged_info->group_list;
		}
		elseif($addon_info->$target_value == 'user_id')
		{
			$tr_val=$logged_info->user_id;
		}
		elseif($addon_info->$target_value=='password')
		{
			$tr_val=$logged_info->password;
		}
		elseif($addon_info->$target_value=='point')
		{
			$target = $logged_info->member_srl;
			$oPointModel = getModel('point');
			$tr_val=$oPointModel->getPoint($target);
		}
		elseif($addon_info->$target_value=='level')
		{
			$oModuleModel = getModel('module');
			$obj = Context::get('logged_info')->member_srl;
			$oPointModel = getModel('point');
			$point = $oPointModel->getPoint($obj);
			$config = $oModuleModel->getModuleConfig('point');
			$tr_val=$oPointModel->getLevel($point,$config->level_step);
		}
		elseif($addon_info->$target_value=='search_target')
		{
			$tr_val=Context::get('search_target');
		}
		elseif($addon_info->$target_value=='search_keyword')
		{
			$tr_val=Context::get('search_keyword');
		}
		elseif($addon_info->$target_value=='page')
		{
			$tr_val=Context::get('page');
		}
		elseif($addon_info->$target_value=='page')
		{
			$tr_val=$logged_info->nick_name;
		}
		elseif($addon_info->$target_value=='mid')
		{
			$tr_val=Context::get('mid');
		}
		elseif($addon_info->$target_value=='member_srl')
		{
			$tr_val=$logged_info->member_srl;
		}
		elseif($addon_info->$target_value=='ip_address')
		{
			$tr_val=$_SERVER['HTTP_CLIENT_IP'];
		}
		elseif($addon_info->$target_value=='ip_proxy_address')
		{
			$tr_val=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif($addon_info->$target_value=='nextsequence')
		{
			$tr_val=getNextSequence();
		}
		elseif($addon_info->$target_value=='referer')
		{
			$tr_val=$_SERVER['HTTP_REFERER'];
		}
		elseif($addon_info->$target_value=='weblanguage')
		{
			$tr_val=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		elseif($addon_info->$target_value=='encord_accept')
		{
			$tr_val=$_SERVER['HTTP_ACCEPT_ENCODING'];
		}
		elseif($addon_info->$target_value=='url')
		{
			$tr_val=$_SERVER['REQUEST_URI'];
		}
		elseif($addon_info->$target_value=='url_http')
		{
			$tr_val=$_SERVER['HTTP_HOST'];
		}
		elseif($addon_info->$target_value=='date')
		{
			$tr_val=date("Ymd");
		}
		elseif($addon_info->$target_value=='monthago')
		{
			$tr_val=date("Ym", strtotime("-".$addon_info->$target_target_value." month"));
		}
		elseif($addon_info->$target_value=='weekago')
		{
			$tr_val=date("Ymd", strtotime("-".$addon_info->$target_target_value." week"));
		}
		elseif($addon_info->$target_value=='dayago')
		{
			$tr_val=date("Ymd", strtotime("-".$addon_info->$target_target_value." day"));
		}
		elseif($addon_info->$target_value=='hoursago')
		{
			$tr_val=date("YmdH", strtotime("-".$addon_info->$target_target_value." hours"));
		}
		elseif($addon_info->$target_value=='secondsago')
		{
			$tr_val=date("YmdHis", strtotime("-".$addon_info->$target_target_value." seconds"));
		}
		
		if($addon_info->$target_numberic_value=='Y') $tr_val=strlen($target_a);
		
		if($var=='a') $target_a = $tr_val;
		if($var=='b') $target_b = $tr_val;
	}

		
	/**
	 * @check conditional sentence
	 **/
	
	//check target
	if($addon_info->list_accept=='')
	{
		if($addon_info->calc_type=='null')
		{
			if(!empty($target_a)||!!(empty($target_a)&&empty($target_b))) return;
		}
		elseif($addon_info->calc_type=='or')
		{
			if(!($target_a!=$target_b)) return;
		}
		elseif($addon_info->calc_type=='and')
		{
			if(!($target_a==$target_b)) return;
		}
		elseif($addon_info->calc_type=='abig')
		{
			if(!($target_a>$target_b)) return;
		}
		elseif($addon_info->calc_type=='bbig')
		{
			if(!($target_a<$target_b)) return;
		}
		elseif($addon_info->calc_type=='same_abig')
		{
			if(!($target_a>=$target_b)) return;
		}
		elseif($addon_info->calc_type=='same_bbig')
		{
			if(!($target_a<=$target_b)) return;
		}
	}
	
	
	/**
	 * @check additional conditional
	 **/
	
	//check logged info
	if($addon_info->act_target=='crawler')
	{
		if(!$isCrawler) return;
	}
	elseif($addon_info->act_target=='member')
	{
		if($logged_info->is_admin) return;
	}
	elseif($addon_info->act_target=='notlogged')
	{
		if($logged_info) return;
	}
	elseif($addon_info->act_target=='notlogged')
	{
		if($logged_info) return;
	}
	elseif($addon_info->act_target=='logged')
	{
		if(!$logged_info) return;
	}
	elseif($addon_info->act_target=='same_member')
	{
		if(!$logged_info->member_srl==$oDocument->variables['member_srl']) return;
	}
	
	/**
	 * @addon called_position
	 **/
	
	if($called_position=='before_display_content')
	{
		//strreplace
		if($addon_info->strreplace=='Y')
		{
			$find_content = $addon_info->search_content;
			$replace_content = $addon_info->replace_content;
			if(Context::get('mid'))
			{
				$oModuleModel = getModel('module');
				$args->module_srl = $oModuleModel->getModuleSrlByMid(Context::get('mid'));
				$args->r_regdate = date("Ymd");
				$tmp_output = executeQuery('addons.select_addon.getNewDocument', $args);
				foreach($tmp_output as $i => $var){}
				$oGroup_list = count($var);
				$find_content = str_replace('[TODAY_COUNT]',$oGroup_list,$find_content);
				$replace_content = str_replace('[TODAY_COUNT]',$oGroup_list,$replace_content);
				if($addon_info->strtype=='') $output = str_replace($find_content,$replace_content,$output);
				if($addon_info->strtype=='preg_replace') $output = preg_replace($find_content,$replace_content,$output);
			}
		}
	}
	
	//check response method
	if(!$addon_info->get_method==NULL && ($addon_info->get_method != $resmethod)) return;
	
	//check called position
	if($called_position==$addon_info->act_position)
	{
		
		
		if (!$addon_info->check_rule==NULL)
		{
			
			//if (preg_match("/\bif\b/i",$addon_info->check_rule))
			//{
				//$i=0;
				//for ($i = 0; $i <= strlen($addon_info->check_rule); $i++) 
				//{
				//	if(substr($addon_info->check_rule,$i,3)=='if(')
					//{
				//		$i=$i+3;
				//		
				//	}
				//}
				//for($z=0;$z=strlen($addon_info->check_rule);$z=$z++)
				//{
					
				//}
			//}
		}
	
		//check user agent
		if(($addon_info->act_agent=='pc' && Mobile::isMobileCheckByAgent()) || ($addon_info->act_agent=='mobile' && !Mobile::isMobileCheckByAgent())) return;
		
		if($addon_info->act_target =='writer')
		{
			$oMembersrl = $oDocument->variables['member_srl'];
		}
	
		//check act
		if($this->act==$addon_info->act_called || ($addon_info->act_called=='dispBoardDocument' && $this->act=='dispBoardContent'))
		{
			
			if($addon_info->chk_context=='Y') Context::addHtmlHeader(print_r(Context::getAll()), false);
			if($addon_info->chk_info=='Y') Context::addHtmlHeader(print_r($addon_info), false);
			if($addon_info->chk_a=='Y') Context::addHtmlHeader('[target_a] => '.$target_a, false);
			if($addon_info->chk_b=='Y') Context::addHtmlHeader('[target_b] => '.$target_b, false);
			if($addon_info->chk_c=='Y') Context::addHtmlHeader('[target_c] => '.$target_c, false);
			
			//logout
			if($addon_info->act_add=='logout')
			{
				$oMemberController = getController('member');
				$oMemberController->procMemberLogout();
			}
			
			//point
			if($addon_info->addpoint)
			{
				if($oMembersrl)
					$member_srl = $oMembersrl;
				elseif($addon_info->membersrls)
					$member_srl = $addon_info->membersrls;
				else
					$member_srl = $logged_info->member_srl;
				$oController = getController('point');
				$oController->setPoint($member_srl, $addon_info->addpoint, 'add');
			}
			
			if($addon_info->minuspoint)
			{
				if($oMembersrl)
					$member_srl = $oMembersrl;
				elseif($addon_info->membersrls)
					$member_srl = $addon_info->membersrls;
				else
					$member_srl = $logged_info->member_srl;
				$oController = getController('point');
				$oController->setPoint($member_srl, $addon_info->minuspoint, 'minus');
			}
			
			//delete
			if($addon_info->doc_delete=='Y')
				if($document_list)
					if($addon_info->list_accept=='Y')
					{
						foreach($document_list as $i=>$var)
						{
							if($addon_info->calc_target_a=='regdate')
							{
								$args->document_srl = $var->variables['document_srl'];
								$tmp_output = executeQuery('addons.select_addon.getDocumentRegdate', $args);
								if($tmp_output->toBool())
									$target_a=$tmp_output->data->regdate;
								if(empty($target_a))
									$target_a=$var->variables['regdate'];
							}
							
							if($addon_info->calc_target_a=='comment_count')
							{
								$args->document_srl = $var->variables['document_srl'];
								$tmp_output = executeQuery('addons.select_addon.getCommentCount', $args);
								if($tmp_output->toBool())
									$target_a=$tmp_output->data->count;
							}
							
							if($addon_info->doc_attr=='is_notice')
							{
								$args->document_srl = $var->variables['document_srl'];
								$tmp_output = executeQuery('addons.select_addon.getisNotice', $args);
								if($tmp_output->toBool())
									if($tmp_output->data->is_notice=='Y')
									{
										$oDocumentController = getController('document');
										$oDocumentController->deleteDocument($var->variables['document_srl']);
									}
							}
							
							if($addon_info->calc_type=='null')
							{
								if(empty($target_a)||(empty($target_a)&&empty($target_b)))
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='or')
							{
								if($target_a!=$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='and')
							{
								if($target_a==$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='abig')
							{
								if($target_a>$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='bbig')
							{
								if($target_a<$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='same_abig')
							{
								if($target_a>=$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
							elseif($addon_info->calc_type=='same_bbig')
							{
								if($target_a<=$target_b)
								{
									$oDocumentController = getController('document');
									$oDocumentController->deleteDocument($var->variables['document_srl']);
								}
							}
						}
					}
					else
					{
						$oDocumentController = getController('document');
						$oDocumentController->deleteDocument($oDocument->variables['document_srl']);
					}
					
			$search_target = array('title', 'content');
			
			foreach($search_target as $type)
			{
				//title,content
				$target = $type.'_input';
				if($addon_info->$target != NULL)
					if($document_list)
						if($addon_info->list_accept=='Y')
						{
							foreach($document_list as $key=>$var)
							{
								$titles=str_replace("[THIS_DATE]",date('l jS \of F Y h:i:s A'),$addon_info->$target);
								$titles=str_replace("[THIS_REGDATE]",$var->variables['regdate'],$titles);
								$titles=str_replace("[THIS_TITLE]",$var->variables[$type],$titles);
								
								//today count
								$oModuleModel = getModel('module');
								$args->module_srl = $oModuleModel->getModuleSrlByMid(Context::get('mid'));
								$args->r_regdate = date("Ymd");
								$tmp_output = executeQuery('addons.select_addon.getNewDocument', $args);
								foreach($tmp_output as $i => $var_dumps){}
								$oGroup_list = count($var_dumps);
								$titles=str_replace("[TODAY_COUNT]",$oGroup_list,$titles);
								
								if($addon_info->calc_target_a=='regdate')
								{
									$args->document_srl = $var->variables['document_srl'];
									$tmp_output = executeQuery('addons.select_addon.getDocumentRegdate', $args);
									if($tmp_output->toBool())
										$target_a=$tmp_output->data->regdate;
									if(empty($target_a))
										$target_a=$var->variables['regdate'];
								}
								
								if($addon_info->calc_target_a=='comment_count')
								{
									$args->document_srl = $var->variables['document_srl'];
									$tmp_output = executeQuery('addons.select_addon.getCommentCount', $args);
									if($tmp_output->toBool())
										$target_a=$tmp_output->data->count;
								}
								
								if($addon_info->doc_attr=='is_notice')
								{
									$args->document_srl = $var->variables['document_srl'];
									$tmp_output = executeQuery('addons.select_addon.getisNotice', $args);
									if($tmp_output->toBool())
										if($tmp_output->data->is_notice=='Y') $var->variables[$type]=$titles;
								}
								
								if($addon_info->calc_target_a=='top_document')
								{
									if($oDocumentModel->getDocumentCount($this->module_srl)==$key)
									{
										$var->variables[$type]=$titles;
									}
								}
								
								if($addon_info->list_position==NULL)
								{
									if($addon_info->calc_type=='null')
									{
										if(empty($target_a)||(empty($target_a)&&empty($target_b))) $var->variables[$type]=$titles;
										
									}
									elseif($addon_info->calc_type=='or')
									{
										if($target_a!=$target_b) $var->variables[$type]=$titles;
									}
									elseif($addon_info->calc_type=='and')
									{
										if($target_a==$target_b) $var->variables[$type]=$titles;
									}
									elseif($addon_info->calc_type=='abig')
									{
										if($target_a>$target_b) $var->variables[$type]=$titles;
									}
									elseif($addon_info->calc_type=='bbig')
									{
										if($target_a<$target_b) $var->variables[$type]=$titles;
									}
									elseif($addon_info->calc_type=='same_abig')
									{
										if($target_a>=$target_b) $var->variables[$type]=$titles;
									}
									elseif($addon_info->calc_type=='same_bbig')
									{
										if($target_a<=$target_b) $var->variables[$type]=$titles;
									}
								}
								
								//document position
								if($addon_info->list_position=='top_document')
								{
									if($oDocumentModel->getDocumentCount($this->module_srl)==$key)
									{
										$var->variables[$type]=$titles;
									}
								}
								
								if($addon_info->list_position=='bottom_document')
								{
									if(1==$key)
									{
										$var->variables[$type]=$titles;
									}
								}
								
							}
						}
						else
						{
							$titles=str_replace("[THIS_DATE]",date('l jS \of F Y h:i:s A'),$addon_info->$target);
							$titles=str_replace("[THIS_REGDATE]",$var->variables['regdate'],$titles);
							$titles=str_replace("[THIS_TITLE]",$var->variables[$type],$titles);
							
							//today count
							$oModuleModel = getModel('module');
							$args->module_srl = $oModuleModel->getModuleSrlByMid(Context::get('mid'));
							$args->r_regdate = date("Ymd");
							$tmp_output = executeQuery('addons.select_addon.getNewDocument', $args);
							foreach($tmp_output as $i => $var_dumps){}
							$oGroup_list = count($var_dumps);
							$titles=str_replace("[TODAY_COUNT]",$oGroup_list,$titles);
							$oDocument->variables[$type]=$titles;
						}
			}
			
			//notice
			if($addon_info->add_notice=='Y')
				if($document_list)
					if($addon_info->list_accept=='Y')
					{
						$type='title';
						foreach($document_list as $key=>$var)
						{
							if($addon_info->doc_attr == 'comment_status')
							{
								$notice_list[$var->variables['document_srl']] = new documentItem();
								$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
								$notice_list[$var->document_srl]->variables = $var->variables;
								Context::set('notice_list',$notice_list);
							}
							if($addon_info->calc_target_a=='regdate')
							{
								$args->document_srl = $var->variables['document_srl'];
								$tmp_output = executeQuery('addons.select_addon.getDocumentRegdate', $args);
								if($tmp_output->toBool())
									$target_a=$tmp_output->data->regdate;
								if(empty($target_a))
									$target_a=$var->variables['regdate'];
							}
							if($addon_info->calc_target_a=='comment_count')
							{
								$args->document_srl = $var->variables['document_srl'];
								$tmp_output = executeQuery('addons.select_addon.getCommentCount', $args);
								if($tmp_output->toBool())
									$target_a=$tmp_output->data->count;
							}
							if($addon_info->calc_target_a=='top_document')
							{
								if($oDocumentModel->getDocumentCount($this->module_srl)==$key)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							
							if($addon_info->calc_type=='null')
							{
								if(empty($target_a)||(empty($target_a)&&empty($target_b)))
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='or')
							{
								if($target_a!=$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='and')
							{
								if($target_a==$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='abig')
							{
								if($target_a>$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='bbig')
							{
								if($target_a<$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='same_abig')
							{
								if($target_a>=$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							elseif($addon_info->calc_type=='same_bbig')
							{
								if($target_a<=$target_b)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							
							//document position
							if($addon_info->list_position=='top_document')
							{
								if($oDocumentModel->getDocumentCount($this->module_srl)==$key)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							
							if($addon_info->list_position=='top_document')
							{
								if(1==$key)
								{
									$notice_list[$var->variables['document_srl']] = new documentItem();
									$notice_list[$var->variables['document_srl']]->document_srl = $var->variables['document_srl'];
									$notice_list[$var->document_srl]->variables = $var->variables;
									Context::set('notice_list',$notice_list);
								}
							}
							
						}
					}
					else
					{
						$oDocument->variables['content']=$addon_info->content_input;
					}
					
			//alert
			if($addon_info->msg_type=='alert_stop')
				$this->stop($addon_info->msg_title);
			elseif($addon_info->msg_title)
				exit($addon_info->msg_type=='alert_exit');
			elseif($addon_info->msg_title)
				echo($addon_info->msg_type=='alert_echo');
			elseif($addon_info->msg_title)
				echo('<script>alert(\''.$addon_info->alert_jsalert.'\');</script>');
			elseif($addon_info->msg_type=='alert_jsheader')
				Context::addHtmlHeader($addon_info->msg_title, false);
			
			if($addon_info->doc_delete=='Y')
			{
				if($addon_info->act_target=='writer' && Context::get('document_srl'))
				{
					if($addon_info->doc_del_srl)
						$document_srl=$addon_info->doc_del_srl;
					else
						$document_srl=Context::get('document_srl');
					$oDocumentController = getController('document');
					$oDocumentController->deleteDocument($document_srl);
				}
			}
			
			//message
			if($addon_info->msg)
			{
				if(!$addon_info->msg_sender)
					$sender = $logged_info->member_srl;
				elseif($oMembersrl)
					$sender = $oMembersrl;
				elseif($addon_info->membersrls)
					$sender = $addon_info->membersrls;
				else
					$sender = $addon_info->msg_sender;
				if(!$addon_info->msg_receiver)
					$receiver = $logged_info->member_srl;
				elseif($oMembersrl)
					$receiver = $oMembersrl;
				elseif($addon_info->membersrls)
					$receiver = $addon_info->membersrls;
				else
					$receiver = $addon_info->msg_receiver;
				$oCommunicationController = &getController('communication');
				$oCommunicationController->sendMessage($sender, $receiver, $addon_info->msg, $addon_info->msg_content, true);
			}
			
			if($addon_info->browser_title)
				Context::setBrowserTitle($addon_info->browser_title);
			
			if($addon_info->chk_status=='Y') Context::addHtmlHeader('[member_srl] => '.$logged_info->member_srl.' [user_id] => '.$logged_info->user_id.' [is_admin] => '.$logged_info->is_admin.' [act] => '.$this->act.' [group_list] => '.$logged_info->group_list, false);
		}
	}
	
?>
