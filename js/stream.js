var tweets_displayed = 10;
vote_lookup = new Array();
vote_lookup[1] = "no_news";
vote_lookup[2] = "news";
vote_lookup[3] = "news_op";

function vote(tweet_id, vote)
{
	var obj = {};
	obj['tweet_id'] = tweet_id;
	obj['vote'] = vote;
	
	var json_string = $.json.encode(obj);
	
	$('#tweet_' + tweet_id).hide();
	$('#tweet_' + tweet_id).remove();
				
	$.ajax({
		type: "GET",
		url: "ajax_loader.php?action=vote",
		cache: false,
		data: "json_string=" + json_string,
		success: function(data)
		{
			var obj = $.json.decode(data);
			
			if(obj['success'] == 1)
			{
				var rated = Number($('#rated').html());
				var rated_score = Number($('#' + vote_lookup[vote]).html());
				rated++;
				rated_score++;
				
				$('#rated').html(rated);
				$('#' + vote_lookup[vote]).html(rated_score);
				
				$('#total_rated').html(obj['total']);
				for(i=1; i<vote_lookup.length; i++)
				{
					$('#total_' + vote_lookup[i]).html(obj['total_' + i]);
				}
				
				tweets_displayed--;
				if(tweets_displayed < 8)
				{
					get_tweets(5);
					tweets_displayed+=5;
				}
			}
			else if(obj['error'] == 1)
			{
				alert(obj['error_message']);
			}
		}
	});
}

function skip(tweet_id)
{
	$('#tweet_' + tweet_id).hide();
	$('#tweet_' + tweet_id).remove();
	
	tweets_displayed--;	
	if(tweets_displayed < 6)
	{
		get_tweets(5);
		tweets_displayed+=5;
	}
}

function get_tweets(limit)
{
	var obj = {};
	obj['limit'] = limit;
	
	var json_string = $.json.encode(obj);
	$.ajax({
		type: "GET",
		url: "ajax_loader.php?action=get_tweets",
		cache: false,
		data: "json_string=" + json_string,
		success: function(data)
		{
			var obj = $.json.decode(data);
			
			for(i=0; i<obj['tweets'].length; i++)
			{
				$('#all_tweets').append(populate_template(obj['tweets'][i], tweet_box_template));
			}
		}
	});
}	