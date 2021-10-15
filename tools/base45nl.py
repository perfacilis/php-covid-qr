import sys

def base45decode_nl(s: str) -> bytes:
    base45_nl_charset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:"

    s_len = len(s)
    res = 0
    for i, c in enumerate(s):
        f = base45_nl_charset.index(c)
        w = 45 ** (s_len - i - 1)
        res += f * w

    return res.to_bytes((res.bit_length() + 7) // 8, byteorder='little')

def main():
    with open("samples/bartwolff.txt") as handle:
        s = handle.readline().rstrip()

    # Strip NL2: header
    encoded = s[4::]

    # Decode
    decoded = base45decode_nl(encoded)

    # Write raw bytes
    sys.stdout.buffer.write(decoded)

if __name__ == '__main__':
    main()