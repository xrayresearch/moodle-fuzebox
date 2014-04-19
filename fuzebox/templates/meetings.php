<?php

/* * ************************************************************ 
 * 
 * Date: Mar 7, 2013
 * version: 1.0
 * programmer: Shani Mahadeva <satyashani@gmail.com>
 * Description:   
 * PHP template file meetings, needs variables - 
 * $day
 * $date
 * $meet(array with keys - meetingid,name,launchurl,attendurl,deleteurl,updateurl)
 * $editable - decides what to show
 * 
 * *************************************************************** */

?>
        <div class="sidebardate fuzebox">
            <p><?php echo $day;?><br /><?php echo $date;?></p>
        </div>

        <div class="meetinginfo fuzebox">
            <div class="fuze_subject fuze_row">
                <b><?php echo $meet["name"];?></b>
            </div>
            <?php if($editable){ ?>
                <a href="<?php echo $meet["deleteurl"];?>" class="fuze_action floatright">
                Delete
                </a>
                <?php if(!$meet["launched"]) { ?>
                    <a href="<?php echo $meet["updateurl"];?>" class="fuze_action floatright">
                    Edit
                    </a>
                    <a href="<?php echo $meet["launchurl"];?>" class="fuze_action floatright">
                    Start
                    </a>
                <?php } else { ?>
                    <a href="<?php echo $meet["viewurl"];?>" class="fuze_action floatright">
                    View
                    </a>                    
                <?php } ?>
            <?php }else{ ?>
                <a href="<?php echo $meet["attendurl"];?>" class="fuze_action floatright">
                    Attend
                </a>
            <?php } ?>
            <div class="fuze_row">
                <span class="title">Invitation : </span> <?php echo $meet["intro"];?>
            </div>
            <div class="fuze_row">
                <span class="title">Scheduled Start : </span> <?php echo $time;?>
            </div>
            
        <?php if($editable){  ?>
            <?php if(!$meet["launched"]) { ?>
            <div class="fuze_row">
                <a href="<?php echo $meet["launchurl"];?>" target="_blank">
                    <?php echo $meet["launchurl"];?>
                </a>
            </div>
            <?php } ?>
            <div class="fuze_row">
                <span class="title">Attendees :</span>
                <div id='fuzeattendees_<?php echo $meet["meetingid"];?>'><img src='<?php echo $loading;?>'/></div>
            </div>
            <span id="info_<?php echo $meet["meetingid"];?>" class="fuze_row"></span>
            <div class="fuze_row">
                <span class="title">Files :</span>
                <div style="margin-left:100px;" id="meetingmedia_<?php echo $meet["meetingid"];?>">
                </div>
            </div>
        <?php }else{ ?>
            <div class="fuze_row">
            <a href="<?php echo $meet["attendurl"];?>" target="_blank">
            <?php echo $meet["attendurl"];?>;
            </a>
            </div>
        <?php }?>
        </div>