import bcrypt

def gen(pw: str) -> str:
    return bcrypt.hashpw(pw.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")

if __name__ == "__main__":
    print("ADMIN:", gen("Admin@123"))
    print("USER1:", gen("User@123"))
    print("USER2:", gen("User@123"))
