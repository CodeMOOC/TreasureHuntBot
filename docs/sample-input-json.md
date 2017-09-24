# Sample JSON payloads

## Sample text message

```
Array
(
    [update_id] => 24063093
    [message] => Array
        (
            [message_id] => 15894
            [from] => Array
                (
                    [id] => 123456789
                    [is_bot] =>
                    [first_name] => Lorenz
                    [last_name] => Klopfenstein
                    [username] => LorenzCK
                    [language_code] => en-US
                )
            [chat] => Array
                (
                    [id] => 123456789
                    [first_name] => Lorenz
                    [last_name] => Klopfenstein
                    [username] => LorenzCK
                    [type] => private
                )
            [date] => 1506207666
            [text] => /start
            [entities] => Array
                (
                    [0] => Array
                        (
                            [offset] => 0
                            [length] => 6
                            [type] => bot_command
                        )
                )
        )
)
```

## Sample callback button

```
Array
(
    [update_id] => 24063094
    [callback_query] => Array
        (
            [id] => 766353158749337023
            [from] => Array
                (
                    [id] => 123456789
                    [is_bot] =>
                    [first_name] => Lorenz
                    [last_name] => Klopfenstein
                    [username] => LorenzCK
                    [language_code] => en-US
                )
            [message] => Array
                (
                    [message_id] => 15895
                    [from] => Array
                        (
                            [id] => 241192783
                            [is_bot] => 1
                            [first_name] => Treasure Hunt bot
                            [username] => treasurehuntbot
                        )
                    [chat] => Array
                        (
                            [id] => 123456789
                            [first_name] => Lorenz
                            [last_name] => Klopfenstein
                            [username] => LorenzCK
                            [type] => private
                        )
                    [date] => 1506207666
                    [text] => Got it
                )
            [chat_instance] => -5521253576649758070
            [data] => datadatadata
        )
)
```
