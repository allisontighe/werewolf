<?php
abstract class taskTypes {
    public const none = 0;
    public const day = 1;
    public const evening = 2;
    public const night = 3;
}
abstract class RoleId {
    public const villager = 0;
    public const werewolf = 1;
    public const clown = 2;
    public const drunk = 3;
    public const slacker = 4;
}
abstract class BotInfo {
    public const username = 'betteradmin_bot';
}
abstract class Interval {
    public const join = 30;
}